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
        $inventory = Inventory::findOrFail($id); // Find inventory by ID
    
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
        $inventory->stocks -= $request->stocks;
        $inventory->save();
    
        return redirect()->back()->with('success', 'Item distributed successfully.');
    }
    
}
