<?php

namespace App\Support\Options;

use App\Http\Resources\KeyValueOptions\GeneralCollection;
use App\Http\Resources\KeyValueOptions\StockMakeIdCollection;
use App\Models\Stock\Stock;
use App\Models\Stock\StockMake;
use App\Models\Stock\StockModel;
use App\Models\Stock\StockTypeCommercial;
use App\Models\Stock\StockTypeGear;
use App\Models\Stock\StockTypeLeisure;
use App\Models\Stock\StockTypeMotorbike;
use App\Models\Stock\StockTypeVehicle;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class StockOptions extends AbstractOptions
{

    public static function types(bool $withAll = false): GeneralCollection
    {
        $items = cache()->rememberForever("stock:type_options:{$withAll}:v1", function() {
            return collect(Stock::STOCK_TYPE_OPTIONS)
                ->map(fn($v) => [
                    'label' => Str::headline($v),
                    'value' => $v,
                ])
                ->values()
                ->all();

        });

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new GeneralCollection($options);
    }

    public static function enumOptions(string $type, string $const, bool $withAll = false): GeneralCollection
    {
        $items = cache()->rememberForever("enum:options:{$type}:{$const}:{$withAll}:v1", function() use ($type, $const) {
            $class = match ($type) {
                Stock::STOCK_TYPE_VEHICLE => StockTypeVehicle::class,
                Stock::STOCK_TYPE_COMMERCIAL => StockTypeCommercial::class,
                Stock::STOCK_TYPE_MOTORBIKE => StockTypeMotorbike::class,
                Stock::STOCK_TYPE_LEISURE => StockTypeLeisure::class,
                Stock::STOCK_TYPE_GEAR => StockTypeGear::class,
                default => null,
            };

            if (!$class) return [];

            // Prefer model constants
            if (defined("$class::$const")) {
                $vals = constant("$class::$const");

                return collect($vals)->map(fn($v) => [
                    'label' => $const === 'DRIVE_TYPE_OPTIONS' ? Str::upper($v) : Str::headline($v),
                    'value' => $v,
                ])
                    ->values()
                    ->all();
            }

        });

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new GeneralCollection($options);
    }

    public static function conditions(?string $type, bool $withAll = false): GeneralCollection
    {
        $items = cache()->rememberForever("stock:condition_options:{$type}:{$withAll}:v1", function() use ($type) {
            $all = fn(array $vals) => array_values(array_unique($vals));

            if ($type === Stock::STOCK_TYPE_VEHICLE) {
                $vals = StockTypeVehicle::CONDITION_OPTIONS;
            } elseif ($type === Stock::STOCK_TYPE_COMMERCIAL) {
                $vals = StockTypeCommercial::CONDITION_OPTIONS;
            } elseif ($type === Stock::STOCK_TYPE_LEISURE) {
                $vals = StockTypeLeisure::CONDITION_OPTIONS;
            } elseif ($type === Stock::STOCK_TYPE_MOTORBIKE) {
                $vals = StockTypeMotorbike::CONDITION_OPTIONS;
            } elseif ($type === Stock::STOCK_TYPE_GEAR) {
                $vals = StockTypeGear::CONDITION_OPTIONS;
            } else {
                $vals = $all(array_merge(
                    StockTypeVehicle::CONDITION_OPTIONS,
                    StockTypeCommercial::CONDITION_OPTIONS,
                    StockTypeLeisure::CONDITION_OPTIONS,
                    StockTypeMotorbike::CONDITION_OPTIONS,
                    StockTypeGear::CONDITION_OPTIONS
                ));
            }

            return collect($vals)->map(fn($v) => ['label' => Str::headline($v), 'value' => $v])->values()->all();

        });

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new GeneralCollection($options);
    }

    public static function colors(?string $type, bool $withAll = false): GeneralCollection
    {
        $items = cache()->rememberForever("stock:color_options:{$type}:{$withAll}:v1", function() use ($type) {
            $vals = match ($type) {
                Stock::STOCK_TYPE_VEHICLE => StockTypeVehicle::COLOR_OPTIONS,
                Stock::STOCK_TYPE_COMMERCIAL => StockTypeCommercial::COLOR_OPTIONS,
                Stock::STOCK_TYPE_LEISURE => StockTypeLeisure::COLOR_OPTIONS,
                Stock::STOCK_TYPE_MOTORBIKE => StockTypeMotorbike::COLOR_OPTIONS,
                default => [],
            };

            return collect($vals)->map(fn($v) => ['label' => Str::headline($v), 'value' => $v])->values()->all();

        });

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new GeneralCollection($options);
    }

    public static function millageRanges(?string $type, string $class, bool $withAll = false): GeneralCollection
    {
        $items = cache()->remember("stock:millage_ranges:{$type}:{$class}:{$withAll}:v1", now()->addHour(), function() use ($type, $class) {
            /** @var class-string<\Illuminate\Database\Eloquent\Model> $class */
            $model = new $class;

            if (!Schema::hasColumn($model->getTable(), 'millage')) {
                return [];
            }

            $min = $class::query()->min('millage');
            $max = $class::query()->max('millage');

            if ($min === null || $max === null) return [];

            $min = (int)$min;
            $max = (int)$max;

            $span = max(1, $max - $min);
            $step = (int)ceil($span / 10);

            $ranges = [];
            $start  = $min;

            for ($i = 0; $i < 10; $i++) {
                $end = ($i === 9) ? $max : min($max, $start + $step - 1);

                $ranges[] = [
                    'label' => number_format($start) . ' - ' . number_format($end),
                    'value' => $start . '-' . $end,
                ];

                $start = $end + 1;
                if ($start > $max) break;
            }

            return $ranges;
        });

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new GeneralCollection($options);
    }

    public static function makesByType(?string $type, bool $withAll = false): GeneralCollection
    {
        $items = cache()->remember("stock:make_options:{$type}:{$withAll}:v1", now()->addHour(), function() use ($type) {
            $typeTable = match ($type) {
                Stock::STOCK_TYPE_VEHICLE => StockTypeVehicle::class,
                Stock::STOCK_TYPE_COMMERCIAL => StockTypeCommercial::class,
                Stock::STOCK_TYPE_LEISURE => StockTypeLeisure::class,
                Stock::STOCK_TYPE_MOTORBIKE => StockTypeMotorbike::class,
                default => null,
            };

            if (!$typeTable) return [];

            $makeIds = $typeTable::query()->distinct()->pluck('make_id')->unique()->values();
            if ($makeIds->isEmpty()) return [];

            // Only makes that actually have stock for this type
            return StockMake::query()
                ->where('stock_type', $type)
                ->whereIn('id', $makeIds->all())
                ->orderBy('name')
                ->select(['id as value', 'name as label'])
                ->get()
                ->map(fn($m) => [
                    'label' => $m->label,
                    'value' => $m->value,
                ])
                ->all();

        });

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new GeneralCollection($options);
    }

    public static function modelsByMakeAndType(?string $type, ?string $makeId, bool $withAll = false): StockMakeIdCollection
    {
        $items = cache()->remember("stock:model_options:{$type}:{$makeId}:{$withAll}:v1", now()->addHour(), function() use ($type, $makeId) {

            $typeTable = match ($type) {
                Stock::STOCK_TYPE_VEHICLE => StockTypeVehicle::class,
                Stock::STOCK_TYPE_COMMERCIAL => StockTypeCommercial::class,
                Stock::STOCK_TYPE_LEISURE => StockTypeLeisure::class,
                Stock::STOCK_TYPE_MOTORBIKE => StockTypeMotorbike::class,
                Stock::STOCK_TYPE_GEAR => StockTypeGear::class,
                default => null,
            };

            if (!$typeTable) {
                return [];
            }

            $model = new $typeTable;

            if (!Schema::hasColumn($model->getTable(), 'model_id')) {
                return [];
            }

            $modelIds = $typeTable::query()
                ->distinct()
                ->pluck('model_id')
                ->filter()
                ->unique()
                ->values();

            if ($modelIds->isEmpty()) return [];

            return StockModel::query()
                ->whereIn('id', $modelIds->all())
                ->when($makeId, function($q) use ($makeId) {
                    $q->where('make_id', $makeId);
                })
                ->orderBy('name')
                ->select(['id as value', 'name as label', 'make_id'])
                ->get()
                ->map(fn($m) => [
                    'label'   => $m->label,
                    'value'   => $m->value,
                    'make_id' => $m->make_id,
                ])
                ->all();
        });

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new StockMakeIdCollection($options);
    }

}
