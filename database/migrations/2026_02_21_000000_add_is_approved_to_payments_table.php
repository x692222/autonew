<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'is_approved')) {
                $table->boolean('is_approved')->default(false)->after('description')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'is_approved')) {
                $table->dropIndex(['is_approved']);
                $table->dropColumn('is_approved');
            }
        });
    }
};

