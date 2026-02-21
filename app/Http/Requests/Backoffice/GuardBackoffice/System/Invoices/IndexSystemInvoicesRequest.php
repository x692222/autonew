<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\Invoices;

use App\Models\Invoice\Invoice;
use App\Support\Validation\Invoices\InvoiceValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexSystemInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('viewAny', Invoice::class)->allowed();
    }

    public function rules(): array
    {
        return app(InvoiceValidationRules::class)->index();
    }
}
