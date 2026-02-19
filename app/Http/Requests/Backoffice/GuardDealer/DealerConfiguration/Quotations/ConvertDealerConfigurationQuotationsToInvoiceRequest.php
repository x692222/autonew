<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Quotations;

use App\Models\Quotation\Quotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ConvertDealerConfigurationQuotationsToInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        $quotation = $this->route('quotation');

        return $quotation instanceof Quotation
            && Gate::forUser($actor)->inspect('dealerConfigurationEditQuotation', $quotation)->allowed()
            && $actor?->hasPermissionTo('createDealershipInvoices', 'dealer');
    }

    public function rules(): array
    {
        $dealerId = (string) $this->user('dealer')?->dealer_id;

        $invoiceIdentifierUniqueRule = Rule::unique('invoices', 'invoice_identifier')
            ->where('dealer_id', $dealerId)
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
