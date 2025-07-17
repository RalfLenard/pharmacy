<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipient;
use App\Models\RecipientDistribution;
use App\Models\Distribution;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RecipientController extends Controller
{
    // This method renders a list of recipients with their related distributions and inventories
    public function index(Request $request)
    {
        $query = Recipient::with([
            'distributions.distribution.inventory'
        ])->latest();
    
        // Basic name search
        if ($search = $request->input('search')) {
            $query->where('full_name', 'like', "%{$search}%");
        }
    
        // Filters
        if ($barangay = $request->input('barangay')) {
            $query->where('barangay', $barangay);
        }
    
        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }
    
        if ($month = $request->input('month')) {
            $query->whereMonth('created_at', $month);
        }
    
        if ($year = $request->input('year')) {
            $query->whereYear('created_at', $year);
        }
    
      // Filter by Inventory brand_name, generic_name, and lot_number
        $query->whereHas('distributions.distribution.inventory', function ($q) use ($request) {
            if ($brand = $request->input('brand_name')) {
                $q->where('brand_name', 'like', "%{$brand}%");
            }
            if ($generic = $request->input('generic_name')) {
                $q->where('generic_name', 'like', "%{$generic}%");
            }
            if ($lotNumber = $request->input('lot_number')) {
                $q->where('lot_number', 'like', "%{$lotNumber}%");
            }
        });

    
        $recipients = $query->paginate(50)->appends($request->all());
    
        return Inertia::render('Recipients', [
            'recipients' => $recipients,
            'filters' => $request->only([
                'search', 'barangay', 'gender', 'month', 'year', 'brand_name', 'generic_name'
            ])
        ]);
    }
    

    // This method fetches distributions where remarks are 'Pharmacy'
    public function medicines()
    {
        // Fetch distributions with related inventory and filter by remarks 'Pharmacy'
        $distributions = Distribution::with('inventory') // Eager load 'inventory'
            ->where('remarks', 'Pharmacy') // Filter by 'remarks' = 'Pharmacy'
            ->orderBy('created_at', 'desc') // Order by created_at in descending order
            ->get();

        // Return the filtered data as a JSON response
        return response()->json($distributions);
    }

    // This method stores a new recipient along with a medicine distribution record
    public function storeRecipientWithMedicine(Request $request)
    {
        // Check if the recipient already exists by full name, birthdate, and barangay
        $recipient = Recipient::where('full_name', $request->full_name)
            ->where('birthdate', $request->birthdate)
            ->where('barangay', $request->barangay)
            ->where('gender', $request->gender) // Fixed: previously used barangay again by mistake
            ->first();

        // Base validation rules (always required)
        $validationRules = [
            'distribution_id' => 'required|exists:distributions,id',
            'quantity' => 'required|integer|min:1',
            'date_given' => 'required|date',
        ];

        // Additional validation if recipient does not exist
        if (!$recipient) {
            $validationRules = array_merge($validationRules, [
                'full_name' => 'required|string|max:255',
                'gender' => 'required|string|max:255',
                'birthdate' => 'required|date',
                'barangay' => 'required|string|max:255',
            ]);
        }

        // Validate the input data
        $validated = $request->validate($validationRules);

        DB::beginTransaction();

        try {
            // Create recipient if not found
            if (!$recipient) {
                $recipient = Recipient::create([
                    'full_name' => $request->full_name,
                    'gender' => $request->gender,
                    'birthdate' => $request->birthdate,
                    'barangay' => $request->barangay,
                ]);
            }

            // Fetch distribution record
            $distribution = Distribution::findOrFail($request->distribution_id);

            // Ensure enough stock is available
            if ($distribution->stocks < $request->quantity) {
                return redirect()->back()->with('error', 'Not enough stock in pharmacy.');
            }

            // Decrease the distribution stock
            $distribution->decrement('stocks', $request->quantity);

            // Record the distribution
            RecipientDistribution::create([
                'recipient_id' => $recipient->id,
                'distribution_id' => $distribution->id,
                'quantity' => $request->quantity,
                'date_given' => $request->date_given,
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Medicine successfully distributed to recipient.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }




    // This method updates an existing recipient distribution record
    public function updateRecipientDistribution(Request $request, $id)
{
    // Validate input data
    $request->validate([
        'distribution_id' => 'required|exists:distributions,id',
        'quantity' => 'required|integer|min:1',
        'date_given' => 'required|date',

        // Recipient fields
        'full_name' => 'required|string|max:255',
        'birthdate' => 'required|date',
        'barangay' => 'required|string|max:255',
        'gender' => 'required|in:Male,Female,Other',
    ]);

    DB::beginTransaction();

    try {
        // Find the existing recipient distribution record
        $recipientDist = RecipientDistribution::findOrFail($id);
        $oldQuantity = $recipientDist->quantity;
        $newQuantity = $request->quantity;

        // Find the distribution records for old and new distribution
        $oldDistribution = Distribution::findOrFail($recipientDist->distribution_id);
        $newDistribution = Distribution::findOrFail($request->distribution_id);

        // Update recipient info
        $recipient = $recipientDist->recipient;
        $recipient->update([
            'full_name' => $request->full_name,
            'birthdate' => $request->birthdate,
            'barangay' => $request->barangay,
            'gender' => $request->gender,
        ]);

        // If medicine (distribution_id) is changed
        if ($recipientDist->distribution_id != $request->distribution_id) {
            // Restore old stock
            $oldDistribution->increment('stocks', $oldQuantity);

            // Check if new distribution has enough stock
            if ($newDistribution->stocks < $newQuantity) {
                return redirect()->back()->with('error', 'Not enough stock in new distribution.');
            }

            // Reduce new distribution stock
            $newDistribution->decrement('stocks', $newQuantity);

            // Update recipient distribution
            $recipientDist->update([
                'distribution_id' => $newDistribution->id,
                'quantity' => $newQuantity,
                'date_given' => $request->date_given,
            ]);

        } else {
            // Same distribution, adjust stock by difference
            $diff = $newQuantity - $oldQuantity;

            if ($diff > 0) {
                if ($newDistribution->stocks < $diff) {
                    return redirect()->back()->with('error', 'Not enough stock to increase quantity.');
                }
                $newDistribution->decrement('stocks', $diff);
            } elseif ($diff < 0) {
                $newDistribution->increment('stocks', abs($diff));
            }

            // Update recipient distribution
            $recipientDist->update([
                'quantity' => $newQuantity,
                'date_given' => $request->date_given,
            ]);
        }

        DB::commit();

        return redirect()->back()->with('success', 'Recipient distribution updated successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
    }
}

    public function show($id)
    {
        // Find the recipient by ID, including their related distributions and inventory
        $recipient = Recipient::with([
            'distributions.distribution.inventory'
        ])->findOrFail($id);

        // Return the recipient details as JSON
        return response()->json($recipient);
    }

    public function storeMedicineOnly(Request $request)
    {
        // Validate input data
        $request->validate([
            'recipient_id' => 'required|exists:recipients,id', // Recipient must exist
            'distribution_id' => 'required|exists:distributions,id', // Medicine must exist
            'quantity' => 'required|integer|min:1', // Quantity must be a positive integer
            'date_given' => 'required|date', // Date must be valid
        ]);

        DB::beginTransaction();

        try {
            // Find the recipient and distribution record
            $recipient = Recipient::findOrFail($request->recipient_id);
            $distribution = Distribution::findOrFail($request->distribution_id);

            // Check if there's enough stock in the distribution
            if ($distribution->stocks < $request->quantity) {
                return redirect()->back()->with('error', 'Not enough stock in pharmacy.');
            }

            // Decrease the distribution stock
            $distribution->decrement('stocks', $request->quantity);

            // Create a new record in RecipientDistribution for this recipient and distribution
            RecipientDistribution::create([
                'recipient_id' => $recipient->id,
                'distribution_id' => $distribution->id,
                'quantity' => $request->quantity,
                'date_given' => $request->date_given,
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Medicine successfully distributed to recipient.');

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback in case of an error
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }


}
