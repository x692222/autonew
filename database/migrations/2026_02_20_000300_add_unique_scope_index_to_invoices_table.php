<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_scope_key', 36)
                ->storedAs("COALESCE(`dealer_id`, 'system')")
                ->after('dealer_id');

            $table->unique(['invoice_scope_key', 'invoice_identifier'], 'invoices_scope_identifier_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_scope_identifier_unique');
            $table->dropColumn('invoice_scope_key');
        });
    }
};
