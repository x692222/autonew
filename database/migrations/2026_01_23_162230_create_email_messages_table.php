<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();

            $table->string('message_id_header')->nullable();
            $table->string('in_reply_to')->nullable();

            $table->string('from_email')->index();
            $table->json('to_email')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();

            $table->string('subject')->nullable();
            $table->json('attachments')->nullable();

            $table->json('raw_headers')->nullable();
            $table->longText('raw_payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
