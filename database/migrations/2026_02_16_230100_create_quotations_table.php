<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dealer_id')->nullable()->constrained('dealers')->restrictOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('quote_identifier', 15);
            $table->boolean('has_custom_quote_identifier')->default(false);
            $table->date('quotation_date');
            $table->unsignedInteger('valid_for_days')->default(0);
            $table->date('valid_until');
            $table->boolean('vat_enabled')->default(false);
            $table->decimal('vat_percentage', 5, 2)->nullable();
            $table->string('vat_number')->nullable();
            $table->decimal('subtotal_before_vat', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->nullableUuidMorphs('created_by');
            $table->nullableUuidMorphs('updated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['dealer_id', 'quotation_date']);
            $table->index(['dealer_id', 'quote_identifier']);
            $table->index(['dealer_id', 'valid_until']);
            $table->index(['dealer_id', 'vat_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};

