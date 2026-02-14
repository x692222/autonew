<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_threads', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('thread_external_id')->nullable(); // provider thread id
            $table->string('from_email')->nullable();
            $table->string('to_email')->nullable();
            $table->json('cc')->nullable();

            $table->string('subject')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['thread_external_id']);
            $table->index(['from_email']);
            $table->index(['to_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_threads');
    }
};
