<?php

namespace App\Support\Stock;

use App\Models\Dealer\Dealer;
use App\Models\Stock\Stock;
use App\Http\Resources\KeyValueOptions\GeneralCollection;
use App\Support\StockHelper;
use App\Support\Options\DealerOptions;
use App\Support\Options\StockOptions;

class StockFormOptionsService
{
    public function createOptions(Dealer $dealer): array
    {
        return [
            'branches' => DealerOptions::branchesList((string) $dealer->id, withAll: false)->resolve(),
            'typeOptions' => StockOptions::types(withAll: false)->resolve(),
            'typeMeta' => StockHelper::stockRelationMeta(),
            'makesByType' => [
                Stock::STOCK_TYPE_VEHICLE => StockOptions::makesByType(Stock::STOCK_TYPE_VEHICLE, withAll: false)->resolve(),
                Stock::STOCK_TYPE_COMMERCIAL => StockOptions::makesByType(Stock::STOCK_TYPE_COMMERCIAL, withAll: false)->resolve(),
                Stock::STOCK_TYPE_LEISURE => StockOptions::makesByType(Stock::STOCK_TYPE_LEISURE, withAll: false)->resolve(),
                Stock::STOCK_TYPE_MOTORBIKE => StockOptions::makesByType(Stock::STOCK_TYPE_MOTORBIKE, withAll: false)->resolve(),
                Stock::STOCK_TYPE_GEAR => StockOptions::makesByType(Stock::STOCK_TYPE_GEAR, withAll: false)->resolve(),
            ],
            'vehicleModelsByMakeId' => collect(StockOptions::modelsByMakeAndType(Stock::STOCK_TYPE_VEHICLE, null, withAll: false)->resolve())
                ->groupBy('make_id')
                ->map(fn ($rows) => (new GeneralCollection(collect($rows)))->resolve())
                ->all(),
            'featureTagsByType' => StockOptions::featureTagsGroupedByType(),
            'enumOptions' => [
                Stock::STOCK_TYPE_VEHICLE => $this->enumSet(Stock::STOCK_TYPE_VEHICLE),
                Stock::STOCK_TYPE_MOTORBIKE => $this->enumSet(Stock::STOCK_TYPE_MOTORBIKE),
                Stock::STOCK_TYPE_LEISURE => $this->enumSet(Stock::STOCK_TYPE_LEISURE),
                Stock::STOCK_TYPE_COMMERCIAL => $this->enumSet(Stock::STOCK_TYPE_COMMERCIAL),
                Stock::STOCK_TYPE_GEAR => $this->enumSet(Stock::STOCK_TYPE_GEAR),
            ],
        ];
    }

    public function editOptions(Dealer $dealer, Stock $stock): array
    {
        $type = (string) $stock->type;

        return [
            'branches' => DealerOptions::branchesList((string) $dealer->id, withAll: false)->resolve(),
            'typeMeta' => StockHelper::stockRelationMeta(),
            'makes' => StockOptions::makesByType($type, withAll: false)->resolve(),
            'vehicleModelsByMakeId' => collect(StockOptions::modelsByMakeAndType(Stock::STOCK_TYPE_VEHICLE, null, withAll: false)->resolve())
                ->groupBy('make_id')
                ->map(fn ($rows) => (new GeneralCollection(collect($rows)))->resolve())
                ->all(),
            'featureOptions' => StockOptions::featureTagsSimpleByType(type: $type)->resolve(),
            'enumOptions' => [
                $type => $this->enumSet($type),
            ],
        ];
    }

    public function enumSet(string $type): array
    {
        return [
            'condition' => StockOptions::enumOptions($type, 'CONDITION_OPTIONS', withAll: false)->resolve(),
            'color' => StockOptions::enumOptions($type, 'COLOR_OPTIONS', withAll: false)->resolve(),
            'gearbox_type' => StockOptions::enumOptions($type, 'GEARBOX_TYPE_OPTIONS', withAll: false)->resolve(),
            'fuel_type' => StockOptions::enumOptions($type, 'FUEL_TYPE_OPTIONS', withAll: false)->resolve(),
            'drive_type' => StockOptions::enumOptions($type, 'DRIVE_TYPE_OPTIONS', withAll: false)->resolve(),
            'category' => StockOptions::enumOptions($type, 'CATEGORY_OPTIONS', withAll: false)->resolve(),
            'police_clearance_ready' => StockOptions::enumOptions($type, 'POLICE_CLEARANCE_STATUS_OPTIONS', withAll: false)->resolve(),
        ];
    }
}
