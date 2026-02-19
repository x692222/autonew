<?php

use App\Enums\QuotationCustomerTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dealer_id')->nullable()->constrained('dealers')->restrictOnDelete();
            $table->enum('type', QuotationCustomerTypeEnum::values())->default(QuotationCustomerTypeEnum::INDIVIDUAL->value);
            $table->string('title')->nullable();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email')->nullable();
            $table->string('contact_number');
            $table->string('address', 150);
            $table->string('vat_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['dealer_id', 'firstname']);
            $table->index(['dealer_id', 'lastname']);
            $table->index(['dealer_id', 'email']);
            $table->index(['dealer_id', 'contact_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

