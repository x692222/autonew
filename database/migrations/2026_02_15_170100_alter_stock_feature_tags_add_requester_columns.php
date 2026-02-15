<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_feature_tags', function (Blueprint $table) {
            $table->foreignUuid('requested_by_user_id')->nullable()->after('stock_type')->constrained('users')->nullOnDelete();
            $table->foreignUuid('requested_by_dealer_user_id')->nullable()->after('requested_by_user_id')->constrained('dealer_users')->nullOnDelete();
            $table->foreignUuid('reviewed_by_user_id')->nullable()->after('requested_by_dealer_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
            $table->index(['is_approved', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_feature_tags', function (Blueprint $table) {
            $table->dropIndex(['is_approved', 'reviewed_at']);
            $table->dropConstrainedForeignId('requested_by_user_id');
            $table->dropConstrainedForeignId('requested_by_dealer_user_id');
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropColumn('reviewed_at');
        });
    }
};

