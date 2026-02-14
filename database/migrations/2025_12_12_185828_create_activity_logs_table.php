<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Polymorphic target being acted on
            $table->uuidMorphs('loggable'); // loggable_type, loggable_id (indexed)

            // Who caused it (optional)
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('dealer_user_id')
                ->nullable()
                ->constrained('dealer_users')
                ->nullOnDelete();

            // What happened
            $table->string('event')->nullable();
            $table->string('description');
            $table->json('properties')->nullable();

            // Context (optional)
            $table->string('ip_address', 45)->nullable(); // IPv4/IPv6
            $table->string('user_agent')->nullable();

            $table->timestamps();

            $table->index(['loggable_type', 'loggable_id', 'event']);
            $table->index(['user_id']);
            $table->index(['dealer_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
