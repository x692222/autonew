<?php

use App\Models\Stock\StockView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_views', function(Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_id')->constrained('stock')->restrictOnDelete();
            $table->boolean('is_sold')->default(false)->index();
            $table->ipAddress('ip_address');
            $table->enum('type', [StockView::VIEW_TYPE_IMPRESSION, StockView::VIEW_TYPE_DETAIL])->index();
            $table->string('country')->index()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_views');
    }
};
