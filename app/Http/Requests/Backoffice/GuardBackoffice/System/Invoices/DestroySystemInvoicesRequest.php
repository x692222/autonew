<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\Invoices;

use App\Models\Invoice\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DestroySystemInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice
            && Gate::inspect('delete', $invoice)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
