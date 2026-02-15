<?php

use App\Enums\SystemRequestStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_requests', function (Blueprint $table) {
            $table->enum('status', SystemRequestStatusEnum::values())
                ->default(SystemRequestStatusEnum::SUBMITTED->value)
                ->index()
                ->after('message');
            $table->longText('response')->nullable()->after('status');
            $table->string('requestable_type')->nullable()->after('dealer_user_id');
            $table->uuid('requestable_id')->nullable()->after('requestable_type');
            $table->index(['requestable_type', 'requestable_id'], 'system_requests_requestable_index');
        });
    }

    public function down(): void
    {
        Schema::table('system_requests', function (Blueprint $table) {
            $table->dropIndex('system_requests_requestable_index');
            $table->dropColumn(['requestable_type', 'requestable_id', 'status', 'response']);
        });
    }
};

