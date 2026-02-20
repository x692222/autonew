<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('total_paid_amount', 11, 2)->default(0)->after('total_amount');
            $table->boolean('is_fully_paid')->default(false)->after('total_paid_amount');
            $table->index(['dealer_id', 'is_fully_paid']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_dealer_id_is_fully_paid_index');
            $table->dropColumn(['total_paid_amount', 'is_fully_paid']);
        });
    }
};

