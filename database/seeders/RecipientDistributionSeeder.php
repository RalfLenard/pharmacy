<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RecipientDistribution;
use App\Models\Recipient;
use App\Models\Inventory;
use Carbon\Carbon;

class RecipientDistributionSeeder extends Seeder
{
    public function run()
    {
        // Create 50 recipient distributions
        for ($i = 0; $i < 50; $i++) {
            // Get a random recipient
            $recipient = Recipient::inRandomOrder()->first();
            
            // Get a random inventory (could be another logic for selecting specific inventory)
            $inventory = Inventory::inRandomOrder()->first();

            // Create the recipient distribution
            RecipientDistribution::create([
                'recipient_id' => $recipient->id, // Random recipient
                'distribution_id' => $inventory->id, // Random inventory (or distribution)
                'quantity' => rand(1, 100), // Random quantity to distribute
                'date_given' => Carbon::now(), // Assign the current date to `date_given`
            ]);
        }
    }
}
