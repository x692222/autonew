<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `location_states` MODIFY `country_id` CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE `location_cities` MODIFY `state_id` CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE `location_suburbs` MODIFY `city_id` CHAR(36) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `location_states` MODIFY `country_id` INT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `location_cities` MODIFY `state_id` INT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `location_suburbs` MODIFY `city_id` INT UNSIGNED NOT NULL');
    }
};
