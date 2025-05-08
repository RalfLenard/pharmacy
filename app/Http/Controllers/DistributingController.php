<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DistributingController extends Controller
{

    public function index()
    {
        $distributed = Distribution::with('inventory')
            ->orderBy('created_at', 'desc')
            ->get();
    
        return Inertia::render('Pharmacy', [
            'distributed' => $distributed,
        ]);
    }
    
    

    public function distribute(Request $request, $id)
    {
        try {
            $request->validate([
                'date_distribute' => 'required|date',
                'quantity' => 'required|integer|min:1',
                'remarks' => 'required|string',
            ]);
    
            $inventory = Inventory::findOrFail($id);
    
            if ($request->quantity > $inventory->stocks) {
                return redirect()->back()->with('error', 'Not enough stocks available to distribute.');
            }
    
            // Check for an existing distribution with same inventory_id, date_distribute, and remarks
            $distribution = Distribution::where('inventory_id', $inventory->id)
                ->where('date_distribute', $request->date_distribute)
                ->where('remarks', $request->remarks)
                ->first();
    
            if ($distribution) {
                // Update existing distribution record
                $distribution->quantity += $request->quantity;
                $distribution->save();
            } else {
                // Create a new distribution record
                $distribution = Distribution::create([
                    'inventory_id' => $inventory->id,
                    'date_distribute' => $request->date_distribute,
                    'quantity' => $request->quantity,
                    'remarks' => $request->remarks,
                    'stocks' => $request->quantity,
                ]);
            }
    
            // Update the inventory quantity
            $inventory->stocks -= $request->quantity;
            $inventory->save();
    
            return redirect()->back()->with('success', 'Item distributed successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An unexpected error occurred: ' . $e->getMessage());
        }
    }
    
    
}
