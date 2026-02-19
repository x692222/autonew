<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('quote_scope_key', 36)
                ->storedAs("COALESCE(`dealer_id`, 'system')")
                ->after('dealer_id');

            $table->unique(['quote_scope_key', 'quote_identifier'], 'quotations_scope_identifier_unique');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropUnique('quotations_scope_identifier_unique');
            $table->dropColumn('quote_scope_key');
        });
    }
};

