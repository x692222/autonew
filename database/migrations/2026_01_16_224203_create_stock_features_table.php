<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_features', function (Blueprint $table) {
            $table->foreignId('stock_id')->constrained('stock')->restrictOnDelete();
            $table->foreignId('feature_id')->constrained('stock_feature_tags')->restrictOnDelete();

            $table->primary(['stock_id', 'feature_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_features');
    }
};
