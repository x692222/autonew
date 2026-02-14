<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_user_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('dealer_user_id')->constrained('dealer_users')->restrictOnDelete();

            /**
             * "Target" reference (class + id) without using uuidMorphs()
             * Example:
             *  target_type = App\Models\Leads\LeadMessage::class
             *  target_id   = 123
             */
            $table->string('target_type'); // fully qualified class name
            $table->uuid('target_id')->nullable(); // nullable in case you want “global” notifications

            /**
             * Where the UI should navigate when the notification is clicked.
             * Example:
             *  route_name   = 'backoffice.leads-management.lead.conversations.show'
             *  route_params = {"lead": 10, "conversation": 55}
             */
            $table->string('route_name');
            $table->json('route_params')->nullable();

            /**
             * Display content
             */
            $table->string('title')->nullable();       // optional short heading
            $table->text('description');               // summary text shown in the panel
            $table->json('meta')->nullable();          // optional extra payload for UI

            /**
             * Read/dismiss state
             */
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Helpful indexes for “unread panel” queries
            $table->index(['dealer_user_id', 'read_at']);
            $table->index(['dealer_user_id', 'dismissed_at']);
            $table->index(['target_type', 'target_id']);
            $table->index(['route_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_user_notifications');
    }
};
