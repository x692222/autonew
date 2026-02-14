<?php

use App\Models\Stock\StockView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_view_summary', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('dealer_id');
            $table->unsignedBigInteger('stock_id');

            $table->enum('type', [StockView::VIEW_TYPE_IMPRESSION, StockView::VIEW_TYPE_DETAIL])->index();
            $table->string('country', 255)->index();

            $table->unsignedBigInteger('total_views')->index();

            $table->date('date');

            $table->timestamps();

            $table->foreign('dealer_id')->references('id')->on('dealers')->onDelete('cascade');
            $table->foreign('stock_id')->references('id')->on('stock')->onDelete('cascade');

            $table->unique(['dealer_id', 'stock_id', 'type', 'country', 'date'], 'svs_unique');

            $table->index(['dealer_id', 'date'], 'svs_dealer_date_idx');
            $table->index(['stock_id', 'date'], 'svs_stock_date_idx');
            $table->index(['type', 'date'], 'svs_type_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_view_summary');
    }
};
