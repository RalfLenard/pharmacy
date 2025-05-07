<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Distribution;
use App\Models\RecipientDistribution;
use Inertia\Inertia;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\View;
use App\Models\Inventory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    
   
public function index()
{
    $user = Auth::user();

    // Common chart data (only for admin)
    $byGender = RecipientDistribution::with('recipient')
        ->get()
        ->groupBy(fn ($record) => $record->recipient->gender ?? 'Unknown')
        ->map(fn ($group) => $group->sum('quantity'));

    $byBarangay = RecipientDistribution::with('recipient')
        ->get()
        ->groupBy(fn ($record) => $record->recipient->barangay ?? 'Unknown')
        ->map(fn ($group) => $group->sum('quantity'));

    $byMedicine = RecipientDistribution::with('distribution.inventory')
        ->get()
        ->groupBy(function ($record) {
            $inventory = $record->distribution->inventory ?? null;
            return $inventory ? "{$inventory->brand_name} ({$inventory->generic_name})" : 'Unknown';
        })
        ->map(fn ($group) => $group->sum('quantity'));

    $inventoryLevels = DB::table('inventories')
        ->select(DB::raw("CONCAT(lot_number, ' - ', brand_name, ' (', generic_name, ')') as name"), 'stocks')
        ->orderBy('lot_number')
        ->get();

    // Admin sees analytics dashboard
    if ($user->usertype === 'admin') {
        return Inertia::render('Dashboard', [
            'charts' => [
                'byGender' => $byGender,
                'byBarangay' => $byBarangay,
                'byMedicine' => $byMedicine,
                'inventoryLevels' => $inventoryLevels,
            ],
        ]);
    } else
    {
        return Inertia::render('Guest');
    }

    // Regular users see a different dashboard
    
}

    public function generateInventoryReport($lot_number)
    {
        $inventories = Inventory::where('lot_number', $lot_number)->get();

        if ($inventories->isEmpty()) {
            return back()->with('error', 'No inventory found for this lot number.');
        }

        $html = View::make('inventoryPdf', compact('inventories', 'lot_number'))->render();

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->pdf();

        return new StreamedResponse(function () use ($pdf) {
            echo $pdf;
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="inventory_lot.pdf"',
        ]);
    }


    public function generateByRemarks($remarks)
    {
        // Get the lot number from the query parameter if it exists
        $lotNumber = request()->query('lot_number');
    
        // Query distributions, filter by remarks and optional lot number
        $query = Distribution::with('inventory')
            ->where('remarks', $remarks);
    
        if ($lotNumber) {
            $query->whereHas('inventory', function($q) use ($lotNumber) {
                $q->where('lot_number', $lotNumber);
            });
        }
    
        $distributions = $query->get();
    
        if ($distributions->isEmpty()) {
            return back()->with('error', 'No distributions found for this remark and lot number combination.');
        }
    
        $html = View::make('distributePdf', compact('distributions', 'remarks'))->render();
    
        $pdf = Browsershot::html($html)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->pdf();
    
            return new StreamedResponse(function () use ($pdf) {
                echo $pdf;
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="distribution_remarks_' . Str::slug($remarks) . '.pdf"',
            ]);
            
    }
    
    public function generateFilteredPDF(Request $request)
    {
        // Eager load necessary relationships
        $query = RecipientDistribution::with(['recipient', 'distribution.inventory']);
    
        // Filter by medicine (generic_name or brand_name)
        if ($request->filled('medicine')) {
            $query->whereHas('distribution.inventory', function ($q) use ($request) {
                $q->where('generic_name', 'like', "%{$request->medicine}%")
                  ->orWhere('brand_name', 'like', "%{$request->medicine}%");
            });
        }
    
        // Filter by lot_number
        if ($request->filled('lot_number')) {
            $query->whereHas('distribution.inventory', function ($q) use ($request) {
                $q->where('lot_number', 'like', "%{$request->lot_number}%");
            });
        }
    
        // Filter by barangay
        if ($request->filled('barangay')) {
            $query->whereHas('recipient', function ($q) use ($request) {
                $q->where('barangay', $request->barangay);
            });
        }
    
        // Filter by gender
        if ($request->filled('gender')) {
            $query->whereHas('recipient', function ($q) use ($request) {
                $q->where('gender', $request->gender);
            });
        }
    
        // Filter by month and year
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('date_given', $request->month)
                  ->whereYear('date_given', $request->year);
        }
    
        // Get the filtered records
        $records = $query->get();
    
        if ($records->isEmpty()) {
            return back()->with('error', 'No records found for the selected filters.');
        }
    
        // Render the HTML for the PDF
        $html = view('recipientPdf', compact('records', 'request'))->render();
    
        // Generate and stream the PDF using Browsershot
        $pdf = Browsershot::html($html)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->pdf();
    
        return new StreamedResponse(function () use ($pdf) {
            echo $pdf;
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="recipient_distribution_filtered.pdf"',
        ]);
    }
    
    
    
}
