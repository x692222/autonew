<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('system_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('dealer_user_id')->nullable()->constrained('dealer_users')->restrictOnDelete();


            $table->string('provider')->index();
            $table->string('message_sid')->unique()->index();

            $table->string('from_wa')->nullable()->index();
            $table->string('to_wa')->nullable()->index();

            $table->string('type')->nullable()->index(); // text/image/document/template/etc
            $table->json('media')->nullable();
            $table->json('raw_payload')->nullable();
            $table->float('cost')->default(0);
            $table->string('profile_name')->nullable()->index();
            $table->longText('body')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
