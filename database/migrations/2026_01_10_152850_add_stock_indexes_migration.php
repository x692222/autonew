<?php

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
        Schema::table('stock', function (Blueprint $table) {
            $table->index(['branch_id', 'deleted_at']);
            $table->index(['branch_id', 'type', 'deleted_at']);
            $table->index(['branch_id', 'published_at', 'deleted_at']);
        });

        Schema::table('dealer_branches', function (Blueprint $table) {
            $table->index(['dealer_id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
