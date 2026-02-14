<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversation_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('conversation_id')
                ->constrained('ai_conversations')
                ->cascadeOnDelete();

            /**
             * role values align with OpenAI messages:
             * developer | system | user | assistant | tool
             */
            $table->string('role');

            // Optional participant name (rarely needed)
            $table->string('name')->nullable();

            // Message content (assistant/user/developer/system)
            $table->longText('content')->nullable();

            // Tool calls returned by the model (assistant message)
            $table->json('tool_calls')->nullable();

            // Tool output identity (tool message)
            $table->string('tool_call_id')->nullable();

            // Token usage (filled for assistant response calls)
            $table->unsignedInteger('tokens_in')->nullable();
            $table->unsignedInteger('tokens_out')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['conversation_id', 'role']);
            $table->index(['tool_call_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversation_messages');
    }
};
