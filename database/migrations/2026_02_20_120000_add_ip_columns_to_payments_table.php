<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('created_from_ip', 45)->nullable()->after('updated_by_id');
            $table->string('updated_from_ip', 45)->nullable()->after('created_from_ip');
            $table->index('created_from_ip');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['created_from_ip']);
            $table->dropColumn(['created_from_ip', 'updated_from_ip']);
        });
    }
};

