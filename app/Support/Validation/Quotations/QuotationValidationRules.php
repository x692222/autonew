<?php

namespace App\Support\Validation\Quotations;

use App\Enums\QuotationLineItemSectionEnum;
use App\Enums\QuotationCustomerTypeEnum;
use App\Models\Dealer\DealerBranch;
use App\Support\Quotations\QuotationSectionOptions;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class QuotationValidationRules
{
    public function index(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'quotation_date_from' => ['nullable', 'date_format:Y-m-d', 'required_with:quotation_date_to', 'before:quotation_date_to'],
            'quotation_date_to' => ['nullable', 'date_format:Y-m-d', 'required_with:quotation_date_from', 'after:quotation_date_from'],
            'valid_until_from' => ['nullable', 'date_format:Y-m-d', 'required_with:valid_until_to', 'before:valid_until_to'],
            'valid_until_to' => ['nullable', 'date_format:Y-m-d', 'required_with:valid_until_from', 'after:valid_until_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'in:quotation_date,quote_identifier,valid_until,customer_firstname,customer_lastname,total_amount,created_at'],
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
        $customerExistsRule = Rule::exists('customers', 'id')->where(
            fn ($query) => $isSystemQuote
                ? $query->whereNull('dealer_id')
                : $query->where('dealer_id', $dealerId ?? '')
        );

        $stockExistsRule = Rule::exists('stock', 'id')->where(
            fn ($query) => $query->whereIn(
                'branch_id',
                DealerBranch::query()
                    ->select('id')
                    ->when(
                        $isSystemQuote,
                        fn ($subQuery) => $subQuery->whereNull('dealer_id'),
                        fn ($subQuery) => $subQuery->where('dealer_id', $dealerId ?? '')
                    )
            )
        );

        $allowedSectionValues = $isSystemQuote
            ? QuotationSectionOptions::valuesForSystem()
            : QuotationSectionOptions::valuesForDealer();
        $subtractiveSectionValues = array_values(array_intersect(
            QuotationSectionOptions::subtractiveValues(),
            $allowedSectionValues
        ));
        $discountSectionValue = QuotationLineItemSectionEnum::DISCOUNTS->value;

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
            'customer_id' => ['required', 'uuid', $customerExistsRule],
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
            'line_items' => [
                'present',
                'array',
                'max:150',
                function (string $attribute, mixed $value, \Closure $fail) use ($subtractiveSectionValues, $discountSectionValue): void {
                    if (! is_array($value)) {
                        return;
                    }

                    $billableLineItemCount = 0;
                    $billableTotal = 0.0;
                    $subtractiveTotal = 0.0;
                    $hasDiscountLineItem = false;
                    $discountTotal = 0.0;

                    foreach ($value as $lineItem) {
                        if (! is_array($lineItem)) {
                            continue;
                        }

                        $section = (string) ($lineItem['section'] ?? '');
                        $lineTotal = (float) ($lineItem['total'] ?? 0);
                        $isSubtractive = in_array($section, $subtractiveSectionValues, true);

                        if (! $isSubtractive) {
                            $billableLineItemCount++;
                            $billableTotal += max(0, $lineTotal);
                        } else {
                            $subtractiveTotal += abs($lineTotal);
                        }

                        if ($section === $discountSectionValue) {
                            $hasDiscountLineItem = true;
                            $discountTotal += abs($lineTotal);
                        }
                    }

                    if ($hasDiscountLineItem && $billableLineItemCount === 0) {
                        $fail('A discount line item requires at least one billable line item.');
                        return;
                    }

                    if ($billableLineItemCount === 0) {
                        $fail('At least one billable line item is required.');
                        return;
                    }

                    if ($hasDiscountLineItem && $discountTotal > ($billableTotal + 0.0000001)) {
                        $fail('Discount total cannot exceed the total of billable line items.');
                        return;
                    }

                    $netTotal = $billableTotal - $subtractiveTotal;
                    if ($netTotal < -0.0000001) {
                        $fail('Total amount cannot be less than 0.');
                    }
                },
            ],
            'line_items.*.section' => ['required', Rule::in($allowedSectionValues)],
            'line_items.*.stock_id' => ['nullable', 'uuid', $stockExistsRule],
            'line_items.*.sku' => ['required', 'string', 'min:3', 'max:35', 'regex:/^\\S+$/'],
            'line_items.*.description' => ['required', 'string', 'max:150'],
            'line_items.*.amount' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'line_items.*.qty' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'line_items.*.total' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'line_items.*.is_vat_exempt' => ['nullable', 'boolean'],
            'customer' => ['nullable', 'array'],
            'customer.type' => ['nullable', Rule::in(QuotationCustomerTypeEnum::values())],
            'customer.title' => ['nullable', 'string', 'max:50'],
            'customer.firstname' => ['nullable', 'string', 'max:255'],
            'customer.lastname' => ['nullable', 'string', 'max:255'],
            'customer.id_number' => ['nullable', 'string', 'max:100'],
            'customer.email' => ['nullable', 'email', 'max:255'],
            'customer.contact_number' => ['nullable', 'regex:/^\\+[1-9]\\d{5,14}$/'],
            'customer.address' => ['nullable', 'string', 'max:150'],
            'customer.vat_number' => ['nullable', 'string', 'max:255'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
