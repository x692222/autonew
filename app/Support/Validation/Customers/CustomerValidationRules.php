<?php

namespace App\Support\Validation\Customers;

use App\Enums\QuotationCustomerTypeEnum;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Rule;

class CustomerValidationRules
{
    public function index(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(QuotationCustomerTypeEnum::values())],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'in:firstname,lastname,type,email,contact_number,quotations_count,invoices_count,payments_count,created_at'],
            'descending' => ['nullable'],
        ];
    }

    public function upsert(?string $dealerId = null, ?string $ignoreCustomerId = null): array
    {
        $emailUniqueRule = Rule::unique('customers', 'email')
            ->whereNull('deleted_at')
            ->when(
                $dealerId === null,
                fn (Unique $rule) => $rule->whereNull('dealer_id'),
                fn (Unique $rule) => $rule->where('dealer_id', $dealerId)
            );

        $contactUniqueRule = Rule::unique('customers', 'contact_number')
            ->whereNull('deleted_at')
            ->when(
                $dealerId === null,
                fn (Unique $rule) => $rule->whereNull('dealer_id'),
                fn (Unique $rule) => $rule->where('dealer_id', $dealerId)
            );

        if ($ignoreCustomerId) {
            $emailUniqueRule = $emailUniqueRule->ignore($ignoreCustomerId);
            $contactUniqueRule = $contactUniqueRule->ignore($ignoreCustomerId);
        }

        return [
            'type' => ['required', Rule::in(QuotationCustomerTypeEnum::values())],
            'title' => ['nullable', 'string', 'max:15'],
            'firstname' => ['required', 'string', 'max:50'],
            'lastname' => ['nullable', 'string', 'max:50'],
            'id_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150', $emailUniqueRule],
            'contact_number' => ['required', 'string', 'max:25', 'regex:/^\\+[1-9]\\d{5,14}$/', $contactUniqueRule],
            'address' => ['required', 'string', 'max:200'],
            'vat_number' => ['nullable', 'string', 'max:35', 'regex:/^[A-Za-z0-9\\/\\-]+$/'],
        ];
    }
}
