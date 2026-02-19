<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Quotations;

use App\Models\Dealer\Dealer;
use App\Models\Quotation\Quotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ConvertDealerQuotationsToInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quotation = $this->route('quotation');
        $dealer = $this->route('dealer');
        $actor = $this->user('backoffice');

        return $quotation instanceof Quotation
            && $dealer instanceof Dealer
            && Gate::inspect('editQuotation', [$dealer, $quotation])->allowed()
            && $actor?->hasPermissionTo('createDealershipInvoices', 'backoffice');
    }

    public function rules(): array
    {
        $dealer = $this->route('dealer');

        $invoiceIdentifierUniqueRule = Rule::unique('invoices', 'invoice_identifier')
            ->where('dealer_id', (string) $dealer?->id)
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
