<?php

use App\Models\Stock\StockTypeLeisure;
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
        Schema::create('stock_type_leisure', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_id')->constrained('stock')->restrictOnDelete();
            $table->foreignUuid('make_id')->constrained('stock')->restrictOnDelete();
            $table->integer('year_model')->index();
            $table->enum('color', StockTypeLeisure::COLOR_OPTIONS)->index();
            $table->enum('condition', StockTypeLeisure::CONDITION_OPTIONS)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_type_leisure');
    }
};
