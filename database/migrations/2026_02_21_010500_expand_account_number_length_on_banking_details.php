<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('banking_details', 'account_number')) {
            DB::statement('ALTER TABLE `banking_details` MODIFY `account_number` VARCHAR(50) NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('banking_details', 'account_number')) {
            DB::statement('ALTER TABLE `banking_details` MODIFY `account_number` VARCHAR(5) NOT NULL');
        }
    }
};

