<?php

use App\Models\System\Configuration\WhatsappProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        WhatsappProvider::query()->firstOrCreate(
            [
                'identifier' => 'log',
            ],
            [
                'config_fields' => [],
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        WhatsappProvider::query()
            ->where('identifier', 'log')
            ->delete();
    }
};
