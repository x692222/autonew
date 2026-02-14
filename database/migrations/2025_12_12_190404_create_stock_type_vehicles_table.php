<?php

use App\Models\Stock\StockTypeVehicle;
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
        Schema::create('stock_type_vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_id')->constrained('stock')->restrictOnDelete();
            $table->foreignUuid('make_id')->constrained('stock')->restrictOnDelete();
            $table->foreignUuid('model_id')->constrained('stock')->restrictOnDelete();
            $table->boolean('is_import')->default(false)->index();
            $table->integer('year_model')->index();
            $table->enum('category', StockTypeVehicle::CATEGORY_OPTIONS)->index();
            $table->enum('color', StockTypeVehicle::COLOR_OPTIONS)->index();
            $table->enum('condition', StockTypeVehicle::CONDITION_OPTIONS)->index();
            $table->enum('gearbox_type', StockTypeVehicle::GEARBOX_TYPE_OPTIONS)->index();
            $table->enum('fuel_type', StockTypeVehicle::FUEL_TYPE_OPTIONS)->index();
            $table->enum('drive_type', StockTypeVehicle::DRIVE_TYPE_OPTIONS)->index();
            $table->integer('millage')->index();
            $table->integer('number_of_seats')->index();
            $table->integer('number_of_doors')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_type_vehicles');
    }
};
