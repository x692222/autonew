<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\Invoices;

use App\Models\Invoice\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CreateSystemInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('create', Invoice::class)->allowed();
    }

    public function rules(): array
    {
        return [
            'return_to' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
