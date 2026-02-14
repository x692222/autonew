<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_threads', function (Blueprint $table) {
            $table->id();

            $table->string('twilio_from')->nullable();
            $table->string('twilio_to')->nullable();

            $table->string('customer_wa')->nullable(); // e.164
            $table->string('dealer_wa')->nullable();   // e.164

            $table->string('profile_name')->nullable()->index();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['customer_wa']);
            $table->index(['dealer_wa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_threads');
    }
};
