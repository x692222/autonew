<?php

namespace App\Support\Validation\Dealers;

use App\Models\Location\LocationSuburb;
use Illuminate\Validation\Rule;

class DealerBranchValidationRules
{
    public function single(bool $requireContactNumbers = false): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'suburb_id' => ['required', 'string', Rule::exists(LocationSuburb::class, 'id')],
            'contact_numbers' => [
                ...($requireContactNumbers ? ['required'] : ['nullable']),
                'string',
                'max:255',
                'regex:/^(?:\\+?\\d+)(?:,\\+?\\d+)*$/',
            ],
            'display_address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function many(string $root = 'branches', bool $requireContactNumbers = true): array
    {
        return [
            $root => ['required', 'array', 'min:1'],
            "{$root}.*.client_key" => ['required', 'string', 'max:100', 'distinct'],
            "{$root}.*.name" => ['required', 'string', 'max:255'],
            "{$root}.*.country_id" => ['nullable', 'string', Rule::exists('location_countries', 'id')],
            "{$root}.*.state_id" => ['nullable', 'string', Rule::exists('location_states', 'id')],
            "{$root}.*.city_id" => ['nullable', 'string', Rule::exists('location_cities', 'id')],
            "{$root}.*.suburb_id" => ['required', 'string', Rule::exists('location_suburbs', 'id')],
            "{$root}.*.contact_numbers" => [
                ...($requireContactNumbers ? ['required'] : ['nullable']),
                'string',
                'max:255',
                'regex:/^(?:\\+?\\d+)(?:,\\+?\\d+)*$/',
            ],
            "{$root}.*.display_address" => ['required', 'string', 'max:255'],
            "{$root}.*.latitude" => ['nullable', 'numeric', 'between:-90,90'],
            "{$root}.*.longitude" => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function normalizeContactNumbers(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = preg_replace('/\s+/', '', $value) ?? '';
        $parts = array_values(array_filter(explode(',', $sanitized), static fn ($part) => $part !== ''));

        return $parts === [] ? null : implode(',', $parts);
    }
}
