<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_stock', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lead_id')
                ->constrained('leads')
                ->restrictOnDelete();

            $table->foreignId('stock_id')
                ->constrained('stock')
                ->restrictOnDelete();

            // Optional but recommended
            $table->unique(['lead_id', 'stock_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_stock');
    }
};
