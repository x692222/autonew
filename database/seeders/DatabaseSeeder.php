<?php

namespace Database\Seeders;

use App\Models\System\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            // DummyCustomerSeeder::class,
            LocationDatasetSeeder::class,
            DealerDatasetSeeder::class,
            // DealerBranchStockSeeder::class,
            StockSeeder::class,
            // LeadDemoSeeder::class,
            WhatsappNumberSeeder::class,
            WhatsappTemplatesSeeder::class
        ]);

    }
}
