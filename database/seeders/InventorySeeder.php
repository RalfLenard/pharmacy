<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;
use Carbon\Carbon;

class InventorySeeder extends Seeder
{
    public function run()
    {
        // Create 5 unique lot numbers
        $lotNumbers = [];
        
        // Generate 5 unique lot numbers
        for ($i = 0; $i < 5; $i++) {
            $lotNumbers[] = 'LOT' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT); // Generate unique lot numbers
        }

        // Duplicate each lot number 10 times to reach exactly 50 total records
        $duplicatedLotNumbers = [];
        foreach ($lotNumbers as $lotNumber) {
            // Add this lot number 10 times
            $duplicatedLotNumbers = array_merge($duplicatedLotNumbers, array_fill(0, 10, $lotNumber));
        }

        // Shuffle the lot numbers to ensure randomness in their distribution
        shuffle($duplicatedLotNumbers);

        // Ensure we have exactly 50 lot numbers (after shuffling)
        $duplicatedLotNumbers = array_slice($duplicatedLotNumbers, 0, 50);

        // Define possible values for brand_name, generic_name, and utils
        $genericNames = ['Acetaminophen', 'Ibuprofen', 'Aspirin', 'Paracetamol', 'Diphenhydramine'];
        $brandNames = ['Tylenol', 'Advil', 'Aspirin', 'Benadryl', 'Panadol'];
        $utils = ['Tablet', 'Capsule', 'Syrup', 'Powder', 'Injection'];

        // Create 50 inventory records with duplicated lot numbers and randomized values
        foreach ($duplicatedLotNumbers as $lotNumber) {
            Inventory::create([
                'date_in' => Carbon::now(),
                'quantity' => rand(50, 200),  // Random quantity between 50 and 200
                'brand_name' => $brandNames[array_rand($brandNames)],  // Randomly select a brand name
                'lot_number' => $lotNumber,
                'expiration_date' => Carbon::now()->addMonths(rand(6, 12)), // Random expiration date within 6 to 12 months
                'generic_name' => $genericNames[array_rand($genericNames)],  // Randomly select a generic name
                'utils' => $utils[array_rand($utils)],  // Randomly select a unit type
                'stocks' => rand(10, 100),  // Random stocks between 10 and 100
            ]);
        }
    }
}
