<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('dealer_id')
                ->constrained('dealers')
                ->cascadeOnDelete();

            // Conversation can belong to anything (Lead, WhatsappThread, etc) or be null for one-off jobs.
            $table->string('owner_type')->nullable();
            $table->uuid('owner_id')->nullable();

            // Optional: what this conversation is used for ("whatsapp", "one_off", "lead_enrichment", etc.)
            $table->string('purpose')->nullable();

            // Model + generation controls
            $table->string('openai_model')->default('gpt-5'); // choose your default
            $table->unsignedInteger('max_output_tokens')->default(600);
            $table->unsignedInteger('min_output_tokens')->default(0); // stored; enforced via instructions
            $table->decimal('temperature', 4, 2)->default(0.30);

            // Lightweight conversation state for token efficiency
            $table->longText('summary')->nullable(); // rolling summary of older messages
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['dealer_id', 'last_used_at']);
            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
