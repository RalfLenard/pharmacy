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
        try {
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
                'stocks' => $validated['quantity'], // Initial stocks = quantity
                'expiration_date' => $validated['expirationDate'],
            ]);
    
            return redirect()->back()->with('success', 'Inventory item added successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'An unexpected error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    
    /**
     * Update an existing inventory item.
     */
    public function update(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'brandName' => 'required|string|max:255',
            'genericName' => 'required|string|max:255',
            'lotNumber' => 'required|string|max:255',
            'utils' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'dateIn' => 'required|date',
            'expirationDate' => 'required|date|after_or_equal:dateIn',
        ]);

        $inventory = Inventory::findOrFail($id);

        $inventory->update([
            'brand_name' => $validated['brandName'],
            'generic_name' => $validated['genericName'],
            'utils' => $validated['utils'],
            'lot_number' => $validated['lotNumber'],
            'quantity' => $validated['quantity'],
            'stocks' => $validated['quantity'], // optionally adjust this logic
            'date_in' => $validated['dateIn'],
            'expiration_date' => $validated['expirationDate'],
        ]);

        return redirect()->route('inventory')->with('success', 'Inventory updated successfully.');
    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->back()
            ->withErrors($e->validator)
            ->withInput();
    } catch (\Exception $e) {
        return redirect()->back()
            ->with('error', 'An unexpected error occurred: ' . $e->getMessage())
            ->withInput();
    }
}

    
    
}
