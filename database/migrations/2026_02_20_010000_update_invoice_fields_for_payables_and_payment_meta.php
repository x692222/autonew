<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['dealer_id', 'valid_until']);
            $table->date('payable_by')->nullable()->after('invoice_date');
            $table->string('purchase_order_number')->nullable()->after('payable_by');
            $table->string('payment_method')->nullable()->after('purchase_order_number');
            $table->string('payment_terms')->nullable()->after('payment_method');

            $table->dropColumn(['valid_for_days', 'valid_until']);

            $table->index(['dealer_id', 'payable_by']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedInteger('valid_for_days')->default(0)->after('invoice_date');
            $table->date('valid_until')->after('valid_for_days');

            $table->dropIndex(['dealer_id', 'payable_by']);
            $table->dropColumn(['payable_by', 'purchase_order_number', 'payment_method', 'payment_terms']);
            $table->index(['dealer_id', 'valid_until']);
        });
    }
};
