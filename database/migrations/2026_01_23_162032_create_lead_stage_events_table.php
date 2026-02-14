<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_stage_events', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('lead_id')->nullable()->constrained('leads')->restrictOnDelete();
            $table->foreignUuid('from_stage_id')->nullable()->constrained('lead_stages')->restrictOnDelete();
            $table->foreignUuid('to_stage_id')->nullable()->constrained('lead_stages')->restrictOnDelete();
            $table->foreignUuid('changed_by_dealer_user_id')->nullable()->constrained('dealer_users')->restrictOnDelete();

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
