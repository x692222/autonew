<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('lead_id')
                ->constrained('leads')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // If the dealer user sets a goal, keep who created it
            $table->foreignUuid('created_by_dealer_user_id')
                ->nullable()
                ->constrained('dealer_users')
                ->nullOnDelete();

            $table->foreignUuid('created_by_backoffice_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('achieved_at')->nullable();
            $table->foreignUuid('achieved_by_dealer_user_id')
                ->nullable()
                ->constrained('dealer_users')
                ->nullOnDelete();

            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['lead_id', 'achieved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_goals');
    }
};
