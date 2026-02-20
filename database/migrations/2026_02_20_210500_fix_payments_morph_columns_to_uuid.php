<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE payments DROP INDEX payments_created_by_type_created_by_id_index');
        DB::statement('ALTER TABLE payments DROP INDEX payments_updated_by_type_updated_by_id_index');

        DB::statement('ALTER TABLE payments CHANGE created_by_id created_by_id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE payments CHANGE updated_by_id updated_by_id CHAR(36) NULL');

        DB::statement('ALTER TABLE payments ADD INDEX payments_created_by_type_created_by_id_index (created_by_type, created_by_id)');
        DB::statement('ALTER TABLE payments ADD INDEX payments_updated_by_type_updated_by_id_index (updated_by_type, updated_by_id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE payments DROP INDEX payments_created_by_type_created_by_id_index');
        DB::statement('ALTER TABLE payments DROP INDEX payments_updated_by_type_updated_by_id_index');

        DB::statement('ALTER TABLE payments CHANGE created_by_id created_by_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE payments CHANGE updated_by_id updated_by_id BIGINT UNSIGNED NULL');

        DB::statement('ALTER TABLE payments ADD INDEX payments_created_by_type_created_by_id_index (created_by_type, created_by_id)');
        DB::statement('ALTER TABLE payments ADD INDEX payments_updated_by_type_updated_by_id_index (updated_by_type, updated_by_id)');
    }
};
