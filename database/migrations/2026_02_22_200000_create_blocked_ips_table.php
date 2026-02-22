<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ip_address', 45);
            $table->string('guard_name', 30);
            $table->unsignedInteger('failed_attempts')->default(0);
            $table->timestamp('blocked_at')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();

            $table->unique(['ip_address', 'guard_name']);
            $table->index('blocked_at');
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_ips');
    }
};
