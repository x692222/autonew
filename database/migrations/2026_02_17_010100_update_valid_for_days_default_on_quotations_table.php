<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('quotations')
            ->where('valid_for_days', '<', 1)
            ->update(['valid_for_days' => 1]);

        DB::statement('ALTER TABLE `quotations` MODIFY `valid_for_days` INT UNSIGNED NOT NULL DEFAULT 1');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `quotations` MODIFY `valid_for_days` INT UNSIGNED NOT NULL DEFAULT 0');
    }
};

