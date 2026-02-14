<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversation_contexts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')
                ->constrained('ai_conversations')
                ->cascadeOnDelete();

            $table->string('context_type');

            $table->json('payload')->nullable();
            $table->longText('payload_text')->nullable();

            $table->unsignedSmallInteger('priority')->default(100);

            $table->timestamp('superseded_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ✅ Explicit short index names (MySQL 64-char limit)
            $table->index(['conversation_id', 'context_type', 'superseded_at'], 'ai_ctx_conv_type_sup');
            $table->index(['conversation_id', 'priority'], 'ai_ctx_conv_pri');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversation_contexts');
    }
};
