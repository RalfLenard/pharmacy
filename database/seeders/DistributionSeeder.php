<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Distribution;
use App\Models\Inventory;
use Carbon\Carbon;

class DistributionSeeder extends Seeder
{
    public function run()
    {
        // Predefined remarks options
        $remarksOptions = ['Pharmacy', 'RHU-II', 'RHU-III', 'RHU-IV'];

        // Seed 50 distribution records with random remarks
        for ($i = 0; $i < 50; $i++) {
            // Get a random inventory
            $inventory = Inventory::inRandomOrder()->first();

            // Randomly select a remarks value from the predefined options
            $remarks = $remarksOptions[array_rand($remarksOptions)];

            Distribution::create([
                'inventory_id' => $inventory->id,
                'date_distribute' => Carbon::now(),
                'quantity' => rand(1, 100),  // Random quantity
                'remarks' => $remarks,  // Random remark from the options
            ]);
        }
    }
}
