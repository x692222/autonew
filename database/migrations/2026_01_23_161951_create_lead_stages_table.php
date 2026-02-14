<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_stages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pipeline_id')->constrained('lead_pipelines')->restrictOnDelete();

            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0)->index();

            $table->boolean('is_terminal')->default(false);
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);

            $table->unsignedInteger('sla_minutes_to_first_response')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_stages');
    }
};
