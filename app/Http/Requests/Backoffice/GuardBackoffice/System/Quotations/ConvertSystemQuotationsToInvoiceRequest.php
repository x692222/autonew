<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\Quotations;

use App\Models\Quotation\Quotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class ConvertSystemQuotationsToInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quotation = $this->route('quotation');
        $actor = $this->user('backoffice');

        return $quotation instanceof Quotation
            && Gate::inspect('update', $quotation)->allowed()
            && $actor?->hasPermissionTo('createSystemInvoices', 'backoffice');
    }

    public function rules(): array
    {
        $invoiceIdentifierUniqueRule = Rule::unique('invoices', 'invoice_identifier')
            ->whereNull('dealer_id')
            ->whereNull('deleted_at');

        return [
            'has_custom_invoice_identifier' => ['nullable', 'boolean'],
            'invoice_identifier' => [
                'nullable',
                'required_if:has_custom_invoice_identifier,1,true',
                'string',
                'max:15',
                'regex:/^[A-Za-z0-9\/\-]+$/',
                $invoiceIdentifierUniqueRule,
            ],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
