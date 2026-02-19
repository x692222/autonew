<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dealer_id')->nullable()->constrained('dealers')->restrictOnDelete();
            $table->foreignUuid('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('invoice_identifier', 15);
            $table->boolean('has_custom_invoice_identifier')->default(false);
            $table->date('invoice_date');
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

            $table->index(['dealer_id', 'invoice_date']);
            $table->index(['dealer_id', 'invoice_identifier']);
            $table->index(['dealer_id', 'valid_until']);
            $table->index(['dealer_id', 'vat_enabled']);
            $table->index(['quotation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
