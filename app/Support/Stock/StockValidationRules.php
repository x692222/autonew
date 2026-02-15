<?php

namespace App\Support\Stock;

use App\Models\Stock\Stock;
use App\Rules\MaxWordCountRule;
use Illuminate\Validation\Rule;

class StockValidationRules
{
    private const YEAR_MODEL_MIN = 1950;
    private const YEAR_MODEL_MAX = 2500;
    private const VEHICLE_SEATS_MIN = 1;
    private const VEHICLE_SEATS_MAX = 80;
    private const VEHICLE_DOORS_MIN = 1;
    private const VEHICLE_DOORS_MAX = 8;
    private const DESCRIPTION_MAX_WORDS = 300;

    public static function baseCreate(): array
    {
        return [
            'branch_id' => ['required', 'string', 'exists:dealer_branches,id'],
            'type' => ['required', 'string', Rule::in(Stock::STOCK_TYPE_OPTIONS)],
            'name' => ['required', 'string', 'max:75'],
            'description' => ['nullable', 'string', new MaxWordCountRule(self::DESCRIPTION_MAX_WORDS)],
            'price' => ['required', 'integer', 'min:0'],
            'internal_reference' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9-]+$/'],
            'published_at' => ['nullable', 'date'],
            'typed' => ['required', 'array'],
            'feature_ids' => ['nullable', 'array'],
            'feature_ids.*' => ['string', 'exists:stock_feature_tags,id'],
            'new_feature_names' => ['nullable', 'array'],
            'new_feature_names.*' => ['string', 'max:255'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public static function baseUpdate(Stock $stock): array
    {
        return [
            'branch_id' => ['required', 'string', 'exists:dealer_branches,id'],
            'type' => ['required', 'string', Rule::in([(string) $stock->type])],
            'name' => ['required', 'string', 'max:75'],
            'description' => ['nullable', 'string', new MaxWordCountRule(self::DESCRIPTION_MAX_WORDS)],
            'price' => ['required', 'integer', 'min:0'],
            'internal_reference' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9-]+$/',
                Rule::unique('stock', 'internal_reference')->ignore((string) $stock->id),
            ],
            'published_at' => ['nullable', 'date'],
            'typed' => ['required', 'array'],
            'feature_ids' => ['nullable', 'array'],
            'feature_ids.*' => ['string', 'exists:stock_feature_tags,id'],
            'new_feature_names' => ['nullable', 'array'],
            'new_feature_names.*' => ['string', 'max:255'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public static function typedRules(string $type): array
    {
        return match ($type) {
            Stock::STOCK_TYPE_VEHICLE => [
                'typed.make_id' => ['required', 'string', 'exists:stock_makes,id'],
                'typed.model_id' => ['required', 'string', 'exists:stock_models,id'],
                'typed.is_import' => ['required', 'boolean'],
                'typed.year_model' => ['required', 'integer', 'min:' . self::YEAR_MODEL_MIN, 'max:' . self::YEAR_MODEL_MAX],
                'typed.category' => ['required', 'string', 'max:50'],
                'typed.color' => ['required', 'string', 'max:50'],
                'typed.condition' => ['required', 'string', 'max:50'],
                'typed.gearbox_type' => ['required', 'string', 'max:50'],
                'typed.fuel_type' => ['required', 'string', 'max:50'],
                'typed.drive_type' => ['required', 'string', 'max:50'],
                'typed.millage' => ['required', 'integer', 'min:0'],
                'typed.number_of_seats' => ['required', 'integer', 'min:' . self::VEHICLE_SEATS_MIN, 'max:' . self::VEHICLE_SEATS_MAX],
                'typed.number_of_doors' => ['required', 'integer', 'min:' . self::VEHICLE_DOORS_MIN, 'max:' . self::VEHICLE_DOORS_MAX],
            ],
            Stock::STOCK_TYPE_COMMERCIAL => [
                'typed.make_id' => ['required', 'string', 'exists:stock_makes,id'],
                'typed.year_model' => ['required', 'integer', 'min:' . self::YEAR_MODEL_MIN, 'max:' . self::YEAR_MODEL_MAX],
                'typed.color' => ['required', 'string', 'max:50'],
                'typed.condition' => ['required', 'string', 'max:50'],
                'typed.gearbox_type' => ['required', 'string', 'max:50'],
                'typed.fuel_type' => ['required', 'string', 'max:50'],
                'typed.millage' => ['required', 'integer', 'min:0'],
            ],
            Stock::STOCK_TYPE_MOTORBIKE => [
                'typed.make_id' => ['required', 'string', 'exists:stock_makes,id'],
                'typed.year_model' => ['required', 'integer', 'min:' . self::YEAR_MODEL_MIN, 'max:' . self::YEAR_MODEL_MAX],
                'typed.category' => ['required', 'string', 'max:50'],
                'typed.color' => ['required', 'string', 'max:50'],
                'typed.condition' => ['required', 'string', 'max:50'],
                'typed.gearbox_type' => ['required', 'string', 'max:50'],
                'typed.fuel_type' => ['required', 'string', 'max:50'],
                'typed.millage' => ['required', 'integer', 'min:0'],
            ],
            Stock::STOCK_TYPE_LEISURE => [
                'typed.make_id' => ['required', 'string', 'exists:stock_makes,id'],
                'typed.year_model' => ['required', 'integer', 'min:' . self::YEAR_MODEL_MIN, 'max:' . self::YEAR_MODEL_MAX],
                'typed.color' => ['required', 'string', 'max:50'],
                'typed.condition' => ['required', 'string', 'max:50'],
            ],
            Stock::STOCK_TYPE_GEAR => [
                'typed.condition' => ['required', 'string', 'max:50'],
            ],
            default => [],
        };
    }
}
