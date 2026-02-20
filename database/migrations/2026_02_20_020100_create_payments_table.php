<?php

use App\Enums\PaymentMethodEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignUuid('dealer_id')->nullable()->constrained('dealers')->restrictOnDelete();
            $table->foreignUuid('banking_detail_id')->nullable()->constrained('banking_details')->restrictOnDelete();
            $table->enum('payment_method', PaymentMethodEnum::values());
            $table->decimal('amount', 11, 2);
            $table->date('payment_date');
            $table->string('description', 255)->nullable();
            $table->uuidMorphs('created_by');
            $table->nullableUuidMorphs('updated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['invoice_id', 'payment_date']);
            $table->index(['dealer_id', 'payment_date']);
            $table->index(['dealer_id', 'payment_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
