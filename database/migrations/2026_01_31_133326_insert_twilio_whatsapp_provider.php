<?php

use App\Models\System\Configuration\WhatsappProvider;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        WhatsappProvider::query()->firstOrCreate(
            [
                'identifier' => 'twilio',
            ],
            [
                'config_fields' => [
                    'accountSid' => 'string',
                    'authToken'  => 'string',
                ],
            ]
        );
    }

    public function down(): void
    {
        WhatsappProvider::query()
            ->where('identifier', 'twilio')
            ->delete();
    }
};
