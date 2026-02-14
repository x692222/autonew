<?php

use App\Models\Leads\Lead;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dealer_id')->constrained('dealers')->restrictOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->restrictOnDelete();

            // Channel
            $table->enum('channel', Lead::LEAD_CHANNELS)->default(Lead::LEAD_CHANNEL_UNKNOWN)->nullable()->index(); // for quick UI filtering

            // Optional metadata
            $table->string('subject')->nullable();
            $table->string('external_ref')->nullable();
            $table->string('participant')->nullable(); // e.g. customer identifier / phone

            // Polymorphic 1:1 thread detail (WhatsappThread / EmailThread)
            $table->nullableMorphs('channelable'); // channelable_type, channelable_id

            // Last message pointers (FK is added later after lead_messages exists)
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->dateTime('last_message_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['dealer_id', 'channel']);
            $table->index(['dealer_id', 'lead_id']);
            $table->index(['dealer_id', 'last_message_at']);
            $table->index(['dealer_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_conversations');
    }
};
