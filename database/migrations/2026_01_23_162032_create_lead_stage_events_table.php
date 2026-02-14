<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_stage_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lead_id')->nullable()->constrained('leads')->restrictOnDelete();
            $table->foreignId('from_stage_id')->nullable()->constrained('lead_stages')->restrictOnDelete();
            $table->foreignId('to_stage_id')->nullable()->constrained('lead_stages')->restrictOnDelete();
            $table->foreignId('changed_by_dealer_user_id')->nullable()->constrained('dealer_users')->restrictOnDelete();

            $table->string('reason')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_stage_events');
    }
};
