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
        Schema::create('stock_feature_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_approved')->default(false)->index();
            $table->enum('stock_type', Stock::STOCK_TYPE_OPTIONS)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['stock_type', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_feature_tags');
    }
};
