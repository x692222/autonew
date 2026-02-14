<?php

use App\Models\WhatsappNumber;
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
        Schema::create('whatsapp_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', WhatsappNumber::TYPE_OPTIONS)->index()->default(WhatsappNumber::TYPE_UNASSIGNED);
            $table->foreignUuid('provider_id')->constrained('system_whatsapp_providers')->restrictOnDelete();
            $table->foreignUuid('dealer_id')->nullable()->constrained('dealers')->restrictOnDelete();
            $table->string('msisdn')->index()->unique();
            $table->json('configuration');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_numbers');
    }
};
