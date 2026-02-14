<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_conversation_summaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dealer_id')
                ->constrained('dealers')
                ->cascadeOnDelete();

            $table->foreignId('conversation_id')
                ->constrained('lead_conversations')
                ->cascadeOnDelete();

            // ✅ Must reference lead_messages (as requested)
            $table->foreignId('last_lead_message_id')
                ->nullable()
                ->constrained('lead_messages')
                ->nullOnDelete();

            // For reporting: which channel + which underlying thread record (whatsapp_threads / email_threads etc.)
            $table->string('channel', 50);
            $table->string('channelable_type')->nullable();
            $table->unsignedBigInteger('channelable_id')->nullable();

            // Rolling window
            $table->dateTime('period_start');
            $table->dateTime('period_end'); // we round to startOfHour in the command

            $table->longText('summary_full');
            $table->longText('summary_delta')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            // ✅ Short index/unique names (avoid mysql 1059)
            $table->unique(['conversation_id', 'period_end'], 'lcs_conv_end_uq');
            $table->index(['dealer_id', 'period_end'], 'lcs_dealer_end_idx');
            $table->index(['channel', 'period_end'], 'lcs_channel_end_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_conversation_summaries');
    }
};
