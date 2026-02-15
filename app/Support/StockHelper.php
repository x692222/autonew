<?php

namespace App\Support;

use App\Models\Stock\Stock;
use App\Models\Stock\StockTypeCommercial;
use App\Models\Stock\StockTypeGear;
use App\Models\Stock\StockTypeLeisure;
use App\Models\Stock\StockTypeMotorbike;
use App\Models\Stock\StockTypeVehicle;
use Illuminate\Database\Eloquent\Builder;

class StockHelper
{
    public static function stockRelationMeta(): array
    {
        return cache()->rememberForever('stock:relation_meta:v2', function() {
            $base = [
                Stock::STOCK_TYPE_VEHICLE    => [
                    'relation' => 'vehicleItem',
                    'table'    => (new StockTypeVehicle())->getTable(),
                    'class'    => StockTypeVehicle::class,
                ],
                Stock::STOCK_TYPE_MOTORBIKE  => [
                    'relation' => 'motorbikeItem',
                    'table'    => (new StockTypeMotorbike())->getTable(),
                    'class'    => StockTypeMotorbike::class,
                ],
                Stock::STOCK_TYPE_LEISURE    => [
                    'relation' => 'leisureItem',
                    'table'    => (new StockTypeLeisure())->getTable(),
                    'class'    => StockTypeLeisure::class,
                ],
                Stock::STOCK_TYPE_COMMERCIAL => [
                    'relation' => 'commercialItem',
                    'table'    => (new StockTypeCommercial())->getTable(),
                    'class'    => StockTypeCommercial::class,
                ],
                Stock::STOCK_TYPE_GEAR       => [
                    'relation' => 'gearItem',
                    'table'    => (new StockTypeGear())->getTable(),
                    'class'    => StockTypeGear::class,
                ],
            ];

            $propertyColumns = [
                'make'    => 'make_id',
                'model'   => 'model_id',
                'import'  => 'is_import',
                'gearbox' => 'gearbox_type',
                'drive'   => 'drive_type',
                'fuel'    => 'fuel_type',
                'millage' => 'millage',
                'color'   => 'color',
            ];

            return collect($base)->map(function(array $meta) use ($propertyColumns) {
                $table = $meta['table'];

                $meta['properties'] = collect($propertyColumns)
                    ->map(fn($column) => tableHasColumn($table, $column))
                    ->all();

                return $meta;
            })->all();
        });
    }

    public static function applyTypeAttributeFilters(Builder $query, array $filters, ?string $type, array $cap): void
    {
        $makeId    = !empty($filters['make_id']) ? (string) $filters['make_id'] : null;
        $modelId   = !empty($filters['model_id']) ? (string) $filters['model_id'] : null;
        $condition = $filters['condition'] ?? null;
        $color     = $filters['color'] ?? null;

        $isImport = $filters['is_import'] ?? null;
        $gearbox  = $filters['gearbox_type'] ?? null;
        $drive    = $filters['drive_type'] ?? null;
        $fuel     = $filters['fuel_type'] ?? null;
        $millage  = $filters['millage_range'] ?? null;

        $relation = match ($type) {
            Stock::STOCK_TYPE_VEHICLE => 'vehicleItem',
            Stock::STOCK_TYPE_COMMERCIAL => 'commercialItem',
            Stock::STOCK_TYPE_LEISURE => 'leisureItem',
            Stock::STOCK_TYPE_MOTORBIKE => 'motorbikeItem',
            Stock::STOCK_TYPE_GEAR => 'gearItem',
            default => null,
        };

        $whereRelation = function(string $rel, \Closure $cb) use ($query) {
            $query->whereHas($rel, $cb);
        };

        $orWhereRelations = function(array $relations, \Closure $cb) use ($query) {
            $query->where(function(Builder $q) use ($relations, $cb) {
                foreach ($relations as $i => $relation) {
                    if ($i === 0) $q->whereHas($relation, $cb);
                    else $q->orWhereHas($relation, $cb);
                }
            });
        };

        // Parse millage range "min-max"
        $millageMin = null;
        $millageMax = null;
        if ($millage && str_contains($millage, '-')) {
            [$a, $b] = array_map('trim', explode('-', $millage, 2));
            if (is_numeric($a) && is_numeric($b)) {
                $millageMin = (int)$a;
                $millageMax = (int)$b;
            }
        }

        // ----------------------------
        // Status filter
        // ----------------------------

        $query->when(($filters['active_status'] ?? null), function(Builder $q, $status) {
            if ($status === 'active') $q->where('is_active', true);
            if ($status === 'inactive') $q->where('is_active', false);
        });

        // ----------------------------
        // Sold filter
        // ----------------------------

        $query->when(($filters['sold_status'] ?? null), function(Builder $q, $status) {
            if ($status === 'sold') $q->where('is_sold', true);
            if ($status === 'unsold') $q->where('is_sold', false);
        });

        // ----------------------------
        // Type filter
        // ----------------------------

        $query->when($type, fn(Builder $q) => $q->where('type', $type));

        // ----------------------------
        // Model filter
        // ----------------------------

        if ($relation && $modelId && ($cap['model'] ?? false)) {
            $whereRelation($relation, fn(Builder $q) => $q->where('model_id', $modelId));
        }

        // ----------------------------
        // Import filter
        // ----------------------------
        if ($relation && $isImport && $cap['import']) {
            $whereRelation($relation, fn(Builder $q) => $q->where('is_import', $isImport === 'yes'));
        }

        // ----------------------------
        // Drive filter
        // ----------------------------
        if ($relation && $drive && ($cap['drive'] ?? false)) {
            $whereRelation($relation, fn(Builder $q) => $q->where('drive_type', $drive));
        }

        // ----------------------------
        // Make filter (only when enabled)
        // ----------------------------
        if ($relation && $makeId && ($cap['make'] ?? false)) {
            $whereRelation($relation, fn(Builder $q) => $q->where('make_id', $makeId));
        }

        // ----------------------------
        // Color filter
        // ----------------------------
        if ($relation && $color && ($cap['color'] ?? false)) {
            $whereRelation($relation, fn(Builder $q) => $q->where('color', $color));
        }

        // ----------------------------
        // Gearbox filter
        // ----------------------------
        if ($relation && $gearbox && ($cap['gearbox'] ?? false)) {
            $whereRelation($relation, fn(Builder $q) => $q->where('gearbox_type', $gearbox));
        }

        // ----------------------------
        // Fuel filter
        // ----------------------------
        if ($relation && $fuel && ($cap['fuel'] ?? false)) {
            $whereRelation($relation, fn(Builder $q) => $q->where('fuel_type', $fuel));
        }

        // ----------------------------
        // Millage filter
        // ----------------------------
        if ($relation && $millageMin !== null && $millageMax !== null && ($cap['millage'] ?? false)) {
            $whereRelation($relation, fn(Builder $q) => $q->whereBetween('millage', [$millageMin, $millageMax]));
        }

        // ----------------------------
        // Condition filter (always valid)
        // ----------------------------
        if ($condition) {
            if ($type) {
                if ($relation) {
                    $whereRelation($relation, fn(Builder $q) => $q->where('condition', $condition));
                }
            } else {
                $orWhereRelations(
                    ['vehicleItem', 'commercialItem', 'leisureItem', 'motorbikeItem', 'gearItem'],
                    fn(Builder $q) => $q->where('condition', $condition)
                );
            }
        }

        // ----------------------------
        // search filter
        // ----------------------------
        $query->when(($filters['search'] ?? null), function(Builder $q, $search) {
            $search = trim((string)$search);
            if ($search === '') return;

            if (method_exists(Stock::class, 'scopeFilterSearch')) {
                $q->filterSearch($search, ['name', 'internal_reference']);
            }
        });
    }

}
