<?php

namespace App\Support\BankingDetails;

class BankingDetailValidationRules
{
    public function index(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }

    public function upsert(): array
    {
        return [
            'bank' => ['required', 'string', 'max:50'],
            'account_holder' => ['required', 'string', 'max:75'],
            'account_number' => ['required', 'string', 'max:25'],
            'branch_name' => ['nullable', 'string', 'max:50'],
            'branch_code' => ['nullable', 'string', 'max:50'],
            'swift_code' => ['nullable', 'string', 'max:20'],
            'other_details' => ['nullable', 'string', 'max:200'],
        ];
    }
}
