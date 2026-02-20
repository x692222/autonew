<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->decimal('amount_verified', 11, 2);
            $table->timestamp('verified_at');
            $table->uuidMorphs('verified_by');
            $table->timestamps();

            $table->index(['payment_id', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_verifications');
    }
};

