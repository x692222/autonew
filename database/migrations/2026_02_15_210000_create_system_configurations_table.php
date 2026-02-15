<?php

use App\Enums\ConfigurationCategoryEnum;
use App\Enums\ConfigurationValueTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('label');
            $table->enum('category', ConfigurationCategoryEnum::values())->index();
            $table->enum('type', ConfigurationValueTypeEnum::values());
            $table->longText('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_configurations');
    }
};
