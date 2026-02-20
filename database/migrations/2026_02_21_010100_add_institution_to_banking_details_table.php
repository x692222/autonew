<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banking_details', function (Blueprint $table) {
            if (! Schema::hasColumn('banking_details', 'institution')) {
                $table->string('institution', 100)->default('')->after('label');
                $table->index(['dealer_id', 'institution']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('banking_details', function (Blueprint $table) {
            if (Schema::hasColumn('banking_details', 'institution')) {
                $table->dropIndex(['dealer_id', 'institution']);
                $table->dropColumn('institution');
            }
        });
    }
};

