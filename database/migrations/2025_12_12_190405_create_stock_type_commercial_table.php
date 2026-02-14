<?php

use App\Models\Stock\StockTypeCommercial;
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
        Schema::create('stock_type_commercial', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_id')->constrained('stock')->restrictOnDelete();
            $table->foreignUuid('make_id')->constrained('stock')->restrictOnDelete();
            $table->integer('year_model')->index();
            $table->enum('color', StockTypeCommercial::COLOR_OPTIONS)->index();
            $table->enum('condition', StockTypeCommercial::CONDITION_OPTIONS)->index();
            $table->enum('gearbox_type', StockTypeCommercial::GEARBOX_TYPE_OPTIONS)->index();
            $table->enum('fuel_type', StockTypeCommercial::FUEL_TYPE_OPTIONS)->index();
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
        Schema::dropIfExists('stock_type_commercial');
    }
};
