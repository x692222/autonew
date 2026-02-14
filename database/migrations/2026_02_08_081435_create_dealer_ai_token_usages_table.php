<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_ai_token_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('dealer_id')->constrained('dealers')->cascadeOnDelete();

            /**
             * Which class/record triggered the AI call.
             * Example:
             *  consumer_type = App\Models\Leads\LeadMessage::class
             *  consumer_id   = 123
             */
            $table->string('consumer_type');
            $table->uuid('consumer_id')->nullable();

            /**
             * Which OpenAI model was used (optional but very handy for reporting).
             * Example: "gpt-4.1-mini"
             */
            $table->string('openai_model')->nullable();

            /**
             * Token accounting
             */
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);

            /**
             * Extra context (optional)
             * e.g. {"purpose":"lead_summary","request_id":"...","latency_ms":1234}
             */
            $table->json('meta')->nullable();

            $table->timestamps();

            // Query helpers / reporting indexes
            $table->index(['dealer_id', 'created_at']);
            $table->index(['consumer_type', 'consumer_id']);
            $table->index(['openai_model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_ai_token_usages');
    }
};
