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
            'label' => ['required', 'string', 'max:100'],
            'institution' => ['required', 'string', 'max:100'],
            'details' => ['required', 'string', 'max:200'],
        ];
    }
}
