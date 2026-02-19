<?php

use App\Enums\PoliceClearanceStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $valuesSql = "'" . implode("','", PoliceClearanceStatusEnum::values()) . "'";
        $default = PoliceClearanceStatusEnum::UNDEFINED->value;

        foreach (['stock_type_vehicles', 'stock_type_commercial'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'is_police_clearance_ready')) {
                continue;
            }

            DB::statement("
                ALTER TABLE `{$table}`
                ADD COLUMN `is_police_clearance_ready_tmp` ENUM({$valuesSql}) NOT NULL DEFAULT '{$default}'
            ");

            DB::statement("
                UPDATE `{$table}`
                SET `is_police_clearance_ready_tmp` = CASE
                    WHEN CAST(`is_police_clearance_ready` AS CHAR) = '1' THEN 'yes'
                    WHEN CAST(`is_police_clearance_ready` AS CHAR) = '0' THEN 'no'
                    WHEN CAST(`is_police_clearance_ready` AS CHAR) IN ({$valuesSql}) THEN CAST(`is_police_clearance_ready` AS CHAR)
                    ELSE '{$default}'
                END
            ");

            DB::statement("
                ALTER TABLE `{$table}`
                DROP COLUMN `is_police_clearance_ready`
            ");

            DB::statement("
                ALTER TABLE `{$table}`
                CHANGE COLUMN `is_police_clearance_ready_tmp` `is_police_clearance_ready` ENUM({$valuesSql}) NOT NULL DEFAULT '{$default}'
            ");
        }
    }

    public function down(): void
    {
        foreach (['stock_type_vehicles', 'stock_type_commercial'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'is_police_clearance_ready')) {
                continue;
            }

            DB::statement("
                ALTER TABLE `{$table}`
                ADD COLUMN `is_police_clearance_ready_tmp` TINYINT(1) NULL DEFAULT NULL
            ");

            DB::statement("
                UPDATE `{$table}`
                SET `is_police_clearance_ready_tmp` = CASE
                    WHEN `is_police_clearance_ready` = 'yes' THEN 1
                    WHEN `is_police_clearance_ready` = 'no' THEN 0
                    ELSE NULL
                END
            ");

            DB::statement("
                ALTER TABLE `{$table}`
                DROP COLUMN `is_police_clearance_ready`
            ");

            DB::statement("
                ALTER TABLE `{$table}`
                CHANGE COLUMN `is_police_clearance_ready_tmp` `is_police_clearance_ready` TINYINT(1) NULL DEFAULT NULL
            ");
        }
    }
};
