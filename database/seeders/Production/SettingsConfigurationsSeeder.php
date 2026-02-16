<?php

namespace Database\Seeders\Production;

use App\Models\Dealer\Dealer;
use App\Support\Settings\ConfigurationManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsConfigurationsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var ConfigurationManager $manager */
        $manager = app(ConfigurationManager::class);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        try {
            DB::table('dealer_configurations')->truncate();
            DB::table('system_configurations')->truncate();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $systemRows = $manager->syncSystemDefaults();

        $dealerCount = 0;
        Dealer::query()
            ->select(['id'])
            ->chunk(200, function ($dealers) use ($manager, &$dealerCount) {
                foreach ($dealers as $dealer) {
                    $manager->syncDealerDefaults($dealer);
                    $dealerCount++;
                }
            });

        $this->command?->info(sprintf(
            'Synced %d system settings and dealer settings for %d dealers.',
            $systemRows->count(),
            $dealerCount
        ));
    }
}
