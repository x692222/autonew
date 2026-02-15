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
        Schema::create('dealer_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dealer_id')->constrained('dealers')->restrictOnDelete();
            $table->string('key');
            $table->string('label');
            $table->enum('category', ConfigurationCategoryEnum::values())->index();
            $table->enum('type', ConfigurationValueTypeEnum::values());
            $table->longText('value')->nullable();
            $table->text('description')->nullable();
            $table->boolean('backoffice_only')->default(false);
            $table->timestamps();

            $table->unique(['dealer_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_configurations');
    }
};
