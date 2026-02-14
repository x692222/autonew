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
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('dealer_branches')->restrictOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_sold')->default(false)->index();
            $table->dateTime('published_at')->nullable()->index();
            $table->string('internal_reference');
            $table->enum('type', Stock::STOCK_TYPE_OPTIONS)->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('price')->index;
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'internal_reference'], 'stock_branch_internal_ref_unique');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};
