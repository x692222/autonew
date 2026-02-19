<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\Invoices;

use App\Models\Invoice\Invoice;
use App\Support\Invoices\InvoiceValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSystemInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice
            && Gate::inspect('update', $invoice)->allowed();
    }

    public function rules(): array
    {
        return app(InvoiceValidationRules::class)->upsert(
            true,
            null,
            $this->route('invoice')?->id,
            false
        );
    }
}
