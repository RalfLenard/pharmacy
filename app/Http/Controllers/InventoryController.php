<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\Inventory;
use App\Models\Supply;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    
     public function index()
    {
        $inventory = Inventory::orderBy('created_at', 'desc')->get();
    
        return Inertia::render('Inventory', [
            'inventory' => $inventory, // Change this to match the prop name
        ]);
    }
    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'dateIn' => 'required|date',
            'brandName' => 'required|string|max:255',
            'genericName' => 'required|string|max:255',
            'utils' => 'required|string|max:255',
            'lotNumber' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'expirationDate' => 'required|date|after_or_equal:dateIn',
        ]);
        
        Inventory::create([
            'date_in' => $validated['dateIn'],
            'brand_name' => $validated['brandName'],
            'generic_name' => $validated['genericName'],
            'utils' => $validated['utils'],
            'lot_number' => $validated['lotNumber'],
            'quantity' => $validated['quantity'],
            'stocks' => $validated['quantity'],
            'expiration_date' => $validated['expirationDate'],
        ]);
        
      

    
        // Redirect back with success message
        return redirect()->back()->with('success', 'Inventory item added successfully!');
    }
    
    /**
     * Update an existing inventory item.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'brandName' => 'required|string|max:255',
            'genericName' => 'required|string|max:255',
            'lotNumber' => 'required|string|max:255',
            'utils' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'dateIn' => 'required|date',
            'expirationDate' => 'required|date|after_or_equal:dateIn',
        ]);
    
        $inventory = Inventory::findOrFail($id);
    
        $inventory->brand_name = $request->brandName;
        $inventory->generic_name = $request->genericName;
        $inventory->utils = $request->utils;
        $inventory->lot_number = $request->lotNumber;
        $inventory->quantity = $request->quantity;
        $inventory->stocks = $request->quantity;
        $inventory->date_in = $request->dateIn;
        $inventory->expiration_date = $request->expirationDate;
    
        $inventory->save();
    
        return redirect()->route('inventory')->with('success', 'Inventory updated successfully.');
    }


    
    
}
