<?php

use App\Enums\LeadCorrespondenceLanguageEnum;
use App\Enums\PoliceClearanceStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->unsignedBigInteger('discounted_price')->nullable()->after('price');
            $table->date('date_acquired')->nullable()->after('discounted_price');
        });

        Schema::table('stock_type_vehicles', function (Blueprint $table) {
            $table->string('vin_number', 100)->nullable()->after('year_model');
            $table->string('engine_number', 100)->nullable()->after('vin_number');
            $table->string('mm_code', 50)->nullable()->after('engine_number');
            $table->enum('is_police_clearance_ready', PoliceClearanceStatusEnum::values())
                ->default(PoliceClearanceStatusEnum::UNDEFINED->value)
                ->after('number_of_doors');
            $table->date('registration_date')->nullable()->after('is_police_clearance_ready');
        });

        Schema::table('stock_type_commercial', function (Blueprint $table) {
            $table->string('vin_number', 100)->nullable()->after('year_model');
            $table->string('engine_number', 100)->nullable()->after('vin_number');
            $table->string('mm_code', 50)->nullable()->after('engine_number');
            $table->enum('is_police_clearance_ready', PoliceClearanceStatusEnum::values())
                ->default(PoliceClearanceStatusEnum::UNDEFINED->value)
                ->after('millage');
            $table->date('registration_date')->nullable()->after('is_police_clearance_ready');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->enum('correspondence_language', LeadCorrespondenceLanguageEnum::values())->nullable()->after('status');
            $table->date('registration_date')->nullable()->after('correspondence_language');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['correspondence_language', 'registration_date']);
        });

        Schema::table('stock_type_commercial', function (Blueprint $table) {
            $table->dropColumn(['vin_number', 'engine_number', 'mm_code', 'is_police_clearance_ready', 'registration_date']);
        });

        Schema::table('stock_type_vehicles', function (Blueprint $table) {
            $table->dropColumn(['vin_number', 'engine_number', 'mm_code', 'is_police_clearance_ready', 'registration_date']);
        });

        Schema::table('stock', function (Blueprint $table) {
            $table->dropColumn(['discounted_price', 'date_acquired']);
        });
    }
};
