<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\Invoices;

use App\Models\Invoice\Invoice;
use App\Support\Invoices\InvoiceValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreSystemInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('create', Invoice::class)->allowed();
    }

    public function rules(): array
    {
        return app(InvoiceValidationRules::class)->upsert(true, null, null);
    }
}
