<?php

namespace App\Support\Validation\Customers;

use App\Enums\QuotationCustomerTypeEnum;
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

    public function upsert(): array
    {
        return [
            'type' => ['required', Rule::in(QuotationCustomerTypeEnum::values())],
            'title' => ['nullable', 'string', 'max:50'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['required', 'regex:/^\\+[1-9]\\d{7,14}$/'],
            'address' => ['required', 'string', 'min:20', 'max:150'],
            'vat_number' => ['nullable', 'string', 'max:35', 'regex:/^[A-Za-z0-9\\/\\-]+$/'],
        ];
    }
}
