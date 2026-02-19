<?php

namespace Database\Seeders\Production;

use App\Models\Dealer\Dealer;
use App\Support\Settings\ConfigurationManager;
use Illuminate\Database\Seeder;

class SyncDealerSettingsDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var ConfigurationManager $manager */
        $manager = app(ConfigurationManager::class);

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
            'Synced dealer configuration defaults for %d dealers.',
            $dealerCount
        ));
    }
}

