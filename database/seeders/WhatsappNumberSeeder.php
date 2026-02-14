<?php

namespace Database\Seeders;

use App\Models\System\Configuration\WhatsappProvider;
use App\Models\WhatsappNumber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WhatsappNumberSeeder extends Seeder
{
    public function run(): void
    {

        // php artisan db:seed --class=Database\\Seeders\\WhatsappNumberSeeder

        $twilio = WhatsappProvider::query()
            ->where('identifier', 'twilio')
            ->firstOrFail();

        // Simple random E.164-ish number: +27 + 9 random digits (adjust as you like)
        $msisdn = '+27' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);

        WhatsappNumber::query()->create([
            'type' => WhatsappNumber::TYPE_SYSTEM,
            'provider_id' => $twilio->id,
            'dealer_id' => null,
            'msisdn' => $msisdn,
            'configuration' => [
                'accountSid' => 'AC' . Str::random(32),
                'authToken'  => Str::random(32),
            ],
        ]);

        $msisdn = '+27' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);

        WhatsappNumber::query()->create([
            'type' => WhatsappNumber::TYPE_DEALER,
            'provider_id' => $twilio->id,
            'dealer_id' => 1,
            'msisdn' => $msisdn,
            'configuration' => [
                'accountSid' => 'AC' . Str::random(32),
                'authToken'  => Str::random(32),
            ],
        ]);
    }
}
