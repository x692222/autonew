<?php

use App\Models\Stock\StockTypeMotorbike;
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
        Schema::create('stock_type_motorbikes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_id')->constrained('stock')->restrictOnDelete();
            $table->foreignUuid('make_id')->constrained('stock')->restrictOnDelete();
            $table->integer('year_model')->index();
            $table->enum('category', StockTypeMotorbike::CATEGORY_OPTIONS)->index();
            $table->enum('color', StockTypeMotorbike::COLOR_OPTIONS)->index();
            $table->enum('condition', StockTypeMotorbike::CONDITION_OPTIONS)->index();
            $table->enum('gearbox_type', StockTypeMotorbike::GEARBOX_TYPE_OPTIONS)->index();
            $table->enum('fuel_type', StockTypeMotorbike::FUEL_TYPE_OPTIONS)->index();
            $table->integer('millage')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_type_motorbikes');
    }
};
