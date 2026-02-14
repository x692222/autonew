<?php

use App\Models\Stock\Stock;
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
        Schema::create('stock_models', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('make_id')->constrained('stock_makes')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_models');
    }
};
