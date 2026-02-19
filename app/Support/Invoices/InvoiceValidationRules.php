<?php

namespace App\Support\Invoices;

use App\Enums\InvoiceCustomerTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class InvoiceValidationRules
{
    public function index(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'customer' => ['nullable', 'string', 'max:100'],
            'invoice_date_from' => ['nullable', 'date_format:Y-m-d'],
            'invoice_date_to' => ['nullable', 'date_format:Y-m-d'],
            'payable_by_from' => ['nullable', 'date_format:Y-m-d'],
            'payable_by_to' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'in:invoice_date,invoice_identifier,total_items_general_accessories,payable_by,customer_firstname,customer_lastname,total_amount,created_at'],
            'descending' => ['nullable'],
        ];
    }

    public function upsert(
        bool $isSystemInvoice,
        ?string $dealerId = null,
        ?string $ignoreInvoiceId = null,
        bool $allowCustomIdentifier = true
    ): array
    {
        $allowedSectionValues = $isSystemInvoice
            ? InvoiceSectionOptions::valuesForSystem()
            : InvoiceSectionOptions::valuesForDealer();

        $invoiceIdentifierUniqueRule = Rule::unique('invoices', 'invoice_identifier')
            ->when(
                $isSystemInvoice,
                fn (Unique $rule) => $rule->whereNull('dealer_id'),
                fn (Unique $rule) => $rule->where('dealer_id', $dealerId ?? '')
            )
            ->whereNull('deleted_at');

        if ($ignoreInvoiceId) {
            $invoiceIdentifierUniqueRule = $invoiceIdentifierUniqueRule->ignore($ignoreInvoiceId);
        }

        return [
            'customer_id' => ['required', 'uuid', Rule::exists('customers', 'id')],
            'has_custom_invoice_identifier' => $allowCustomIdentifier ? ['nullable', 'boolean'] : ['prohibited'],
            'invoice_identifier' => [
                ...($allowCustomIdentifier
                    ? [
                        'nullable',
                        'required_if:has_custom_invoice_identifier,1,true',
                        'string',
                        'max:15',
                        'regex:/^[A-Za-z0-9\\/\\-]+$/',
                        $invoiceIdentifierUniqueRule,
                    ]
                    : ['prohibited']
                ),
            ],
            'invoice_date' => ['required', 'date_format:Y-m-d'],
            'payable_by' => ['nullable', 'date_format:Y-m-d'],
            'purchase_order_number' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.section' => ['required', Rule::in($allowedSectionValues)],
            'line_items.*.stock_id' => ['nullable', 'uuid', Rule::exists('stock', 'id')],
            'line_items.*.sku' => ['nullable', 'string', 'min:3', 'max:35', 'regex:/^\\S+$/'],
            'line_items.*.description' => ['required', 'string', 'max:255'],
            'line_items.*.amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'line_items.*.qty' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'line_items.*.total' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'line_items.*.is_vat_exempt' => ['nullable', 'boolean'],
            'customer' => ['nullable', 'array'],
            'customer.type' => ['nullable', Rule::in(InvoiceCustomerTypeEnum::values())],
            'customer.title' => ['nullable', 'string', 'max:50'],
            'customer.firstname' => ['nullable', 'string', 'max:255'],
            'customer.lastname' => ['nullable', 'string', 'max:255'],
            'customer.id_number' => ['nullable', 'string', 'max:100'],
            'customer.email' => ['nullable', 'email', 'max:255'],
            'customer.contact_number' => ['nullable', 'regex:/^\\+[1-9]\\d{7,14}$/'],
            'customer.address' => ['nullable', 'string', 'max:150'],
            'customer.vat_number' => ['nullable', 'string', 'max:255'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
