<?php

use App\Models\Stock\StockPublishLog;
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
        Schema::create('stock_publish_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stock')->restrictOnDelete();
            $table->enum('action', [StockPublishLog::ACTION_PUBLISH, StockPublishLog::ACTION_UNPUBLISH])->index();
            $table->unsignedBigInteger('by_user_id')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_publish_logs');
    }
};
