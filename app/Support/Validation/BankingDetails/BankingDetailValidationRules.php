<?php

namespace App\Support\Validation\BankingDetails;

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
        return $this->upsertForPrefix();
    }

    public function upsertMany(string $root = 'banking_details', bool $required = false): array
    {
        return array_merge(
            [
                $root => $required ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            ],
            $this->upsertForPrefix("{$root}.*.")
        );
    }

    private function upsertForPrefix(string $prefix = ''): array
    {
        return [
            "{$prefix}bank" => ['required', 'string', 'max:50'],
            "{$prefix}account_holder" => ['required', 'string', 'max:75'],
            "{$prefix}account_number" => ['required', 'string', 'max:25'],
            "{$prefix}branch_name" => ['nullable', 'string', 'max:50'],
            "{$prefix}branch_code" => ['nullable', 'string', 'max:50'],
            "{$prefix}swift_code" => ['nullable', 'string', 'max:20'],
            "{$prefix}other_details" => ['nullable', 'string', 'max:200'],
        ];
    }
}
