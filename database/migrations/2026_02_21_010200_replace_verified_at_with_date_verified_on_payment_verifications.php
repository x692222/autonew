<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_verifications', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_verifications', 'date_verified')) {
                $table->timestamp('date_verified')->nullable()->after('amount_verified');
            }
        });

        if (Schema::hasColumn('payment_verifications', 'verified_at') && Schema::hasColumn('payment_verifications', 'date_verified')) {
            DB::table('payment_verifications')->update([
                'date_verified' => DB::raw('verified_at'),
            ]);
        }

        Schema::table('payment_verifications', function (Blueprint $table) {
            if (Schema::hasColumn('payment_verifications', 'verified_at')) {
                $table->dropColumn('verified_at');
            }

            $table->index(['payment_id', 'date_verified'], 'payment_verifications_payment_id_date_verified_index');
        });
    }

    public function down(): void
    {
        Schema::table('payment_verifications', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_verifications', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('amount_verified');
            }
        });

        if (Schema::hasColumn('payment_verifications', 'date_verified') && Schema::hasColumn('payment_verifications', 'verified_at')) {
            DB::table('payment_verifications')->update([
                'verified_at' => DB::raw('date_verified'),
            ]);
        }

        Schema::table('payment_verifications', function (Blueprint $table) {
            $table->dropIndex('payment_verifications_payment_id_date_verified_index');

            if (Schema::hasColumn('payment_verifications', 'date_verified')) {
                $table->dropColumn('date_verified');
            }
        });
    }
};

