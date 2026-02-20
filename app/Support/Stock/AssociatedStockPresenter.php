<?php

namespace App\Support\Stock;

use App\Models\Stock\Stock;
use App\Support\StockHelper;

class AssociatedStockPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(?Stock $stock): array
    {
        $meta = StockHelper::stockRelationMeta()[$stock?->type] ?? null;
        $relation = $meta['relation'] ?? null;
        $typed = $relation ? $stock?->{$relation} : null;
        $cap = $meta['properties'] ?? [];
        $typedAttributes = $typed?->getAttributes() ?? [];

        $hasTypedAttribute = static fn (string $attribute) => array_key_exists($attribute, $typedAttributes);

        $fields = [
            'make' => (bool) ($cap['make'] ?? false),
            'model' => (bool) ($cap['model'] ?? false),
            'millage' => (bool) ($cap['millage'] ?? false),
            'is_police_clearance_ready' => (bool) ($cap['police_clearance'] ?? false),
            'condition' => $hasTypedAttribute('condition'),
            'is_import' => (bool) ($cap['import'] ?? false),
            'gearbox_type' => (bool) ($cap['gearbox'] ?? false),
            'drive_type' => (bool) ($cap['drive'] ?? false),
            'fuel_type' => (bool) ($cap['fuel'] ?? false),
            'color' => (bool) ($cap['color'] ?? false),
        ];

        return [
            'stock_id' => $stock?->id,
            'internal_reference' => $stock?->internal_reference,
            'name' => $stock?->name,
            'is_active' => (bool) ($stock?->is_active ?? false),
            'is_sold' => (bool) ($stock?->is_sold ?? false),
            'is_paid' => (bool) ($stock?->is_paid ?? false),
            'type' => (string) ($stock?->type ?? '-'),
            'fields' => $fields,
            'make' => $fields['make'] ? $typed?->make?->name : null,
            'model' => $fields['model'] ? $typed?->model?->name : null,
            'millage' => $fields['millage'] ? $typed?->millage : null,
            'is_police_clearance_ready' => $fields['is_police_clearance_ready']
                ? ($typed?->is_police_clearance_ready?->value ?? $typed?->is_police_clearance_ready)
                : null,
            'condition' => $fields['condition'] ? $typed?->condition : null,
            'is_import' => $fields['is_import'] ? $typed?->is_import : null,
            'gearbox_type' => $fields['gearbox_type'] ? $typed?->gearbox_type : null,
            'drive_type' => $fields['drive_type'] ? $typed?->drive_type : null,
            'fuel_type' => $fields['fuel_type'] ? $typed?->fuel_type : null,
            'color' => $fields['color'] ? $typed?->color : null,
        ];
    }
}

