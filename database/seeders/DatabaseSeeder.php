<?php

namespace Database\Seeders;

use Database\Seeders\Development\AssignAdminAllPermissionsSeeder;
use Database\Seeders\Development\AssignDealerUserAllPermissionsSeeder;
use Database\Seeders\Development\DealerDatasetSeeder;
use Database\Seeders\Development\LocationDatasetSeeder;
use Database\Seeders\Development\StockSeeder;
use Database\Seeders\Development\WhatsappNumberSeeder;
use Database\Seeders\Development\WhatsappTemplatesSeeder;
use Database\Seeders\Production\PermissionsSeeder;
use Database\Seeders\Production\SettingsConfigurationsSeeder;
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
            PermissionsSeeder::class,
            AssignAdminAllPermissionsSeeder::class,
            AssignDealerUserAllPermissionsSeeder::class, // uithaal in prod

            // DummyCustomerSeeder::class,
//            LocationDatasetSeeder::class,
//            DealerDatasetSeeder::class,
            // DealerBranchStockSeeder::class,
//            StockSeeder::class,
            // LeadDemoSeeder::class,
//            WhatsappNumberSeeder::class,
//            WhatsappTemplatesSeeder::class,
            SettingsConfigurationsSeeder::class, // uithaal vir prod
        ]);

    }
}
