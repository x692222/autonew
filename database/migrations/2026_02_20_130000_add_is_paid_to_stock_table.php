<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('is_sold');
            $table->index('is_paid');
        });
    }

    public function down(): void
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->dropIndex(['is_paid']);
            $table->dropColumn('is_paid');
        });
    }
};

