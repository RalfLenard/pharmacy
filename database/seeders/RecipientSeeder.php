<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recipient;
use Faker\Factory as Faker;
use Carbon\Carbon;

class RecipientSeeder extends Seeder
{
    public function run()
    {
        // Instantiate Faker to generate random data
        $faker = Faker::create();

        // Create 50 random recipients
        for ($i = 0; $i < 50; $i++) {
            Recipient::create([
                'full_name' => $faker->name, // Random name
                'birthdate' => $faker->date($format = 'Y-m-d', $max = 'now'), // Random birthdate
                'barangay' => $faker->word, // Random barangay name
                'gender' => $faker->randomElement(['male', 'female']), // Random gender
            ]);
        }
    }
}
