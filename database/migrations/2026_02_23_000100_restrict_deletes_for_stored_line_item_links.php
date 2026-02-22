<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_line_items', function (Blueprint $table) {
            $table->dropForeign(['stored_line_item_id']);
            $table->foreign('stored_line_item_id')
                ->references('id')
                ->on('stored_line_items')
                ->restrictOnDelete();
        });

        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->dropForeign(['stored_line_item_id']);
            $table->foreign('stored_line_item_id')
                ->references('id')
                ->on('stored_line_items')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotation_line_items', function (Blueprint $table) {
            $table->dropForeign(['stored_line_item_id']);
            $table->foreign('stored_line_item_id')
                ->references('id')
                ->on('stored_line_items')
                ->nullOnDelete();
        });

        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->dropForeign(['stored_line_item_id']);
            $table->foreign('stored_line_item_id')
                ->references('id')
                ->on('stored_line_items')
                ->nullOnDelete();
        });
    }
};

