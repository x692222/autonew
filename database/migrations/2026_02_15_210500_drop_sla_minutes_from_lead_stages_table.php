<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('lead_stages', 'sla_minutes_to_first_response')) {
            Schema::table('lead_stages', function (Blueprint $table) {
                $table->dropColumn('sla_minutes_to_first_response');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('lead_stages', 'sla_minutes_to_first_response')) {
            Schema::table('lead_stages', function (Blueprint $table) {
                $table->unsignedInteger('sla_minutes_to_first_response')->nullable();
            });
        }
    }
};
