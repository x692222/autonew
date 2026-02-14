<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_messages', function (Blueprint $table) {
            $table->id();

            $table->boolean('is_read')->default(false);

            // Billing / scoping (must exist even when unassigned)
            $table->foreignId('dealer_id')->constrained('dealers')->restrictOnDelete();
            $table->foreignId('conversation_id')->constrained('lead_conversations')->restrictOnDelete();
            $table->foreignId('created_by_dealer_user_id')->nullable()->constrained('dealer_users')->restrictOnDelete();
            $table->foreignId('system_user_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->string('channel'); // whatsapp/email/etc
            $table->enum('direction', ['inbound', 'outbound'])->default('inbound');

            $table->text('body')->nullable();
            $table->longText('body_html')->nullable();
            $table->string('preview')->nullable();

            $table->string('status')->nullable(); // queued/sent/delivered/read/failed
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('read_at')->nullable();

            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();

            // Polymorphic 1:1 message payload (WhatsappMessage / EmailMessage)
            $table->nullableMorphs('messageable'); // messageable_type, messageable_id

            $table->timestamps();
            $table->softDeletes();

            $table->index(['dealer_id', 'conversation_id', 'created_at']);
            $table->index(['dealer_id', 'created_at']);
            $table->index(['dealer_id', 'channel', 'direction']);
            $table->index(['dealer_id', 'deleted_at']);
        });

        // Add the FK for lead_conversations.last_message_id now that lead_messages exists.
        Schema::table('lead_conversations', function (Blueprint $table) {
            $table->foreign('last_message_id')
                ->references('id')
                ->on('lead_messages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lead_conversations', function (Blueprint $table) {
            $table->dropForeign(['last_message_id']);
        });

        Schema::dropIfExists('lead_messages');
    }
};
