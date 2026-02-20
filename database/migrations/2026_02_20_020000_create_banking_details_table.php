<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banking_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dealer_id')->nullable()->constrained('dealers')->restrictOnDelete();
            $table->string('label', 100);
            $table->text('details');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['dealer_id', 'created_at']);
            $table->index(['dealer_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banking_details');
    }
};

