<?php

namespace App\Support\Validation\Invoices;

use App\Enums\InvoiceLineItemSectionEnum;
use App\Enums\InvoiceCustomerTypeEnum;
use App\Models\Dealer\DealerBranch;
use App\Support\Invoices\InvoiceSectionOptions;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class InvoiceValidationRules
{
    public function index(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'invoice_date_from' => ['nullable', 'date_format:Y-m-d', 'required_with:invoice_date_to', 'before:invoice_date_to'],
            'invoice_date_to' => ['nullable', 'date_format:Y-m-d', 'required_with:invoice_date_from', 'after:invoice_date_from'],
            'payable_by_from' => ['nullable', 'date_format:Y-m-d', 'required_with:payable_by_to', 'before:payable_by_to'],
            'payable_by_to' => ['nullable', 'date_format:Y-m-d', 'required_with:payable_by_from', 'after:payable_by_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'in:invoice_date,is_fully_paid,is_fully_verified,invoice_identifier,payable_by,customer_firstname,customer_lastname,total_amount,total_paid_amount,total_due,created_at'],
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
        $customerExistsRule = Rule::exists('customers', 'id')->where(
            fn ($query) => $isSystemInvoice
                ? $query->whereNull('dealer_id')
                : $query->where('dealer_id', $dealerId ?? '')
        );

        $stockExistsRule = Rule::exists('stock', 'id')->where(
            fn ($query) => $query->whereIn(
                'branch_id',
                DealerBranch::query()
                    ->select('id')
                    ->when(
                        $isSystemInvoice,
                        fn ($subQuery) => $subQuery->whereNull('dealer_id'),
                        fn ($subQuery) => $subQuery->where('dealer_id', $dealerId ?? '')
                    )
            )
        );

        $allowedSectionValues = $isSystemInvoice
            ? InvoiceSectionOptions::valuesForSystem()
            : InvoiceSectionOptions::valuesForDealer();
        $subtractiveSectionValues = array_values(array_intersect(
            InvoiceSectionOptions::subtractiveValues(),
            $allowedSectionValues
        ));
        $discountSectionValue = InvoiceLineItemSectionEnum::DISCOUNTS->value;

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
            'customer_id' => ['required', 'uuid', $customerExistsRule],
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
            'purchase_order_number' => ['nullable', 'string', 'max:50'],
            'payment_terms' => ['nullable', 'string', 'max:50'],
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
            'customer.type' => ['nullable', Rule::in(InvoiceCustomerTypeEnum::values())],
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
