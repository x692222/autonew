<?php

namespace Database\Seeders\Development;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $totalRecords = 10000;
        $batchSize = 1000;

        for ($i = 0; $i < $totalRecords; $i += $batchSize) {
            $customers = [];

            for ($j = 0; $j < $batchSize; $j++) {
                $customers[] = [
                    'firstname' => $faker->firstName(),
                    'lastname'  => $faker->lastName(),
                    'contact_no'=> $faker->phoneNumber(),
                    'email'     => $faker->unique()->safeEmail(),
                    'city'      => $faker->city(),
                    'country'   => $faker->country(),
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ];
            }

            DB::table('dummy_customers')->insert($customers);
        }
    }
}
