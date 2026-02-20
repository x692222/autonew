<?php

namespace App\Support\Quotations;

use App\Enums\QuotationCustomerTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class QuotationValidationRules
{
    public function index(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'customer' => ['nullable', 'string', 'max:100'],
            'quotation_date_from' => ['nullable', 'date_format:Y-m-d'],
            'quotation_date_to' => ['nullable', 'date_format:Y-m-d'],
            'valid_until_from' => ['nullable', 'date_format:Y-m-d'],
            'valid_until_to' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'in:quotation_date,quote_identifier,total_items_general_accessories,valid_until,customer_firstname,customer_lastname,total_amount,created_at'],
            'descending' => ['nullable'],
        ];
    }

    public function upsert(
        bool $isSystemQuote,
        ?string $dealerId = null,
        ?string $ignoreQuotationId = null,
        bool $allowCustomIdentifier = true
    ): array
    {
        $allowedSectionValues = $isSystemQuote
            ? QuotationSectionOptions::valuesForSystem()
            : QuotationSectionOptions::valuesForDealer();

        $quoteIdentifierUniqueRule = Rule::unique('quotations', 'quote_identifier')
            ->when(
                $isSystemQuote,
                fn (Unique $rule) => $rule->whereNull('dealer_id'),
                fn (Unique $rule) => $rule->where('dealer_id', $dealerId ?? '')
            )
            ->whereNull('deleted_at');

        if ($ignoreQuotationId) {
            $quoteIdentifierUniqueRule = $quoteIdentifierUniqueRule->ignore($ignoreQuotationId);
        }

        return [
            'customer_id' => ['required', 'uuid', Rule::exists('customers', 'id')],
            'has_custom_quote_identifier' => $allowCustomIdentifier ? ['nullable', 'boolean'] : ['prohibited'],
            'quote_identifier' => [
                ...($allowCustomIdentifier
                    ? [
                        'nullable',
                        'required_if:has_custom_quote_identifier,1,true',
                        'string',
                        'max:15',
                        'regex:/^[A-Za-z0-9\\/\\-]+$/',
                        $quoteIdentifierUniqueRule,
                    ]
                    : ['prohibited']
                ),
            ],
            'quotation_date' => ['required', 'date_format:Y-m-d'],
            'valid_for_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.section' => ['required', Rule::in($allowedSectionValues)],
            'line_items.*.stock_id' => ['nullable', 'uuid', Rule::exists('stock', 'id')],
            'line_items.*.sku' => ['nullable', 'string', 'min:3', 'max:35', 'regex:/^\\S+$/'],
            'line_items.*.description' => ['required', 'string', 'max:255'],
            'line_items.*.amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'line_items.*.qty' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'line_items.*.total' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'line_items.*.is_vat_exempt' => ['nullable', 'boolean'],
            'customer' => ['nullable', 'array'],
            'customer.type' => ['nullable', Rule::in(QuotationCustomerTypeEnum::values())],
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
