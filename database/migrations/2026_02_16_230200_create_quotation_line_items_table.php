<?php

use App\Enums\QuotationLineItemSectionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignUuid('dealer_id')->nullable()->constrained('dealers')->restrictOnDelete();
            $table->foreignUuid('stock_id')->nullable()->constrained('stock')->nullOnDelete();
            $table->enum('section', QuotationLineItemSectionEnum::values());
            $table->string('sku')->nullable();
            $table->string('description');
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('total', 14, 2)->default(0);
            $table->boolean('is_vat_exempt')->default(false);
            $table->timestamps();

            $table->index(['quotation_id', 'section']);
            $table->index(['dealer_id', 'section', 'sku']);
            $table->index(['dealer_id', 'section', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_line_items');
    }
};

