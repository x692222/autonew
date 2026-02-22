<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE invoices MODIFY purchase_order_number VARCHAR(50) NULL');
            DB::statement('ALTER TABLE invoices MODIFY payment_terms VARCHAR(50) NULL');
            DB::statement('ALTER TABLE invoices DROP COLUMN payment_method');
            return;
        }

        if (Schema::hasColumn('invoices', 'payment_method')) {
            Schema::table('invoices', function ($table): void {
                $table->dropColumn('payment_method');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE invoices MODIFY purchase_order_number VARCHAR(255) NULL');
            DB::statement('ALTER TABLE invoices MODIFY payment_terms VARCHAR(255) NULL');
            DB::statement('ALTER TABLE invoices ADD COLUMN payment_method VARCHAR(255) NULL AFTER purchase_order_number');
            return;
        }

        if (! Schema::hasColumn('invoices', 'payment_method')) {
            Schema::table('invoices', function ($table): void {
                $table->string('payment_method')->nullable()->after('purchase_order_number');
            });
        }
    }
};
