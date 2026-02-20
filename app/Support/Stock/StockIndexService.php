<?php

namespace App\Support\Stock;

use App\Enums\PoliceClearanceStatusEnum;
use App\Models\Stock\Stock;
use App\Models\Stock\StockTypeCommercial;
use App\Models\Stock\StockTypeGear;
use App\Models\Stock\StockTypeLeisure;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\Stock\StockTypeMotorbike;
use App\Models\Stock\StockTypeVehicle;
use App\Support\Options\DealerOptions;
use App\Support\Options\GeneralOptions;
use App\Support\Options\StockOptions;
use App\Support\StockHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class StockIndexService
{
    public function capabilities(?string $type): array
    {
        $meta = StockHelper::stockRelationMeta();

        $defaults = [
            'make' => false,
            'model' => false,
            'import' => false,
            'gearbox' => false,
            'drive' => false,
            'fuel' => false,
            'millage' => false,
            'color' => false,
            'police_clearance' => true,
        ];

        if (! $type || ! isset($meta[$type])) {
            return $defaults;
        }

        return array_replace($defaults, $meta[$type]['properties'] ?? []);
    }

    public function columns(bool $includeDealer): array
    {
        $columns = [];

        if ($includeDealer) {
            $columns[] = [
                'name' => 'dealer_name',
                'label' => 'Dealer',
                'sortable' => true,
                'align' => 'left',
                'field' => 'dealer_name',
            ];
        }

        return array_merge($columns, [
            ['name' => 'branch_name', 'label' => 'Branch', 'sortable' => true, 'align' => 'left', 'field' => 'branch_name'],
            ['name' => 'type_title', 'label' => 'Type', 'sortable' => true, 'align' => 'left', 'field' => 'type_title'],
            ['name' => 'internal_reference', 'label' => 'Reference', 'sortable' => true, 'align' => 'left', 'field' => 'internal_reference'],
            ['name' => 'is_live', 'label' => 'Is Live', 'sortable' => false, 'align' => 'center', 'field' => 'is_live'],
            ['name' => 'payment_status', 'label' => 'Payments', 'sortable' => false, 'align' => 'center', 'field' => 'payment_status'],
            ['name' => 'price', 'label' => 'Price', 'sortable' => true, 'align' => 'right', 'field' => 'price', 'numeric' => true],
            ['name' => 'discounted_price', 'label' => 'Discounted Price', 'sortable' => true, 'align' => 'right', 'field' => 'discounted_price', 'numeric' => true],
            ['name' => 'condition', 'label' => 'Condition', 'sortable' => true, 'align' => 'left', 'field' => 'condition'],
            ['name' => 'stock_images_count', 'label' => 'Images', 'sortable' => true, 'align' => 'right', 'field' => 'stock_images_count', 'numeric' => true],
            ['name' => 'published_at', 'label' => 'Published', 'sortable' => true, 'align' => 'left', 'field' => 'published_at'],
            ['name' => 'name', 'label' => 'Name', 'sortable' => true, 'align' => 'left', 'field' => 'name'],
            ['name' => 'active_label', 'label' => 'Active', 'sortable' => true, 'align' => 'left', 'field' => 'active_label'],
            ['name' => 'sold_label', 'label' => 'Sold', 'sortable' => true, 'align' => 'left', 'field' => 'sold_label'],
            ['name' => 'make_name', 'label' => 'Make', 'sortable' => true, 'align' => 'left', 'field' => 'make_name'],
            ['name' => 'model_name', 'label' => 'Model', 'sortable' => true, 'align' => 'left', 'field' => 'model_name'],
            ['name' => 'millage', 'label' => 'Millage', 'sortable' => true, 'align' => 'right', 'field' => 'millage', 'numeric' => true],
            ['name' => 'color_title', 'label' => 'Color', 'sortable' => true, 'align' => 'left', 'field' => 'color_title'],
            ['name' => 'gearbox_type', 'label' => 'Gearbox', 'sortable' => true, 'align' => 'left', 'field' => 'gearbox_type'],
            ['name' => 'drive_type', 'label' => 'Drive', 'sortable' => true, 'align' => 'left', 'field' => 'drive_type'],
            ['name' => 'fuel_type', 'label' => 'Fuel', 'sortable' => true, 'align' => 'left', 'field' => 'fuel_type'],
            ['name' => 'is_import_label', 'label' => 'Import', 'sortable' => true, 'align' => 'left', 'field' => 'is_import_label'],
        ]);
    }

    public function options(bool $isBackoffice, ?string $dealerId, ?string $type, ?string $makeId, array $capabilities): array
    {
        $meta = StockHelper::stockRelationMeta();

        $branches = [];
        if ($dealerId) {
            $branches = DealerOptions::branchesList($dealerId, withAll: true)->resolve();
        }

        $isImportOptions = [];
        if ($type && ($capabilities['import'] ?? false)) {
            $isImportOptions = GeneralOptions::isImportOptions(withAll: true)->resolve();
        }

        $gearboxTypeOptions = [];
        if ($type && ($capabilities['gearbox'] ?? false)) {
            $gearboxTypeOptions = StockOptions::enumOptions($type, 'GEARBOX_TYPE_OPTIONS', withAll: true)->resolve();
        }

        $driveTypeOptions = [];
        if ($type && ($capabilities['drive'] ?? false)) {
            $driveTypeOptions = StockOptions::enumOptions($type, 'DRIVE_TYPE_OPTIONS', withAll: true)->resolve();
        }

        $fuelTypeOptions = [];
        if ($type && ($capabilities['fuel'] ?? false)) {
            $fuelTypeOptions = StockOptions::enumOptions($type, 'FUEL_TYPE_OPTIONS', withAll: true)->resolve();
        }

        $millageRanges = [];
        if ($type && ($capabilities['millage'] ?? false) && isset($meta[$type]['class'])) {
            $millageRanges = StockOptions::millageRanges($type, $meta[$type]['class'], withAll: true)->resolve();
        }

        return [
            'dealers' => $isBackoffice ? DealerOptions::dealersList(withAll: true)->resolve() : [],
            'branches' => $branches,
            'typeOptions' => StockOptions::types(withAll: true)->resolve(),
            'activeStatusOptions' => GeneralOptions::activeOptions(withAll: true)->resolve(),
            'soldStatusOptions' => GeneralOptions::isSoldOptions(withAll: true)->resolve(),
            'policeClearanceReadyOptions' => collect(PoliceClearanceStatusEnum::cases())
                ->map(fn (PoliceClearanceStatusEnum $item) => [
                    'label' => ucfirst($item->value),
                    'value' => $item->value,
                ])
                ->prepend(['label' => 'All', 'value' => ''])
                ->values()
                ->all(),
            'conditionOptions' => StockOptions::conditions($type, withAll: true)->resolve(),
            'colorOptions' => StockOptions::colors($type, withAll: true)->resolve(),
            'makes' => StockOptions::makesByType($type, withAll: true)->resolve(),
            'models' => StockOptions::modelsByMakeAndType($type, $makeId, withAll: true)->resolve(),
            'isImportOptions' => $isImportOptions,
            'gearboxTypeOptions' => $gearboxTypeOptions,
            'driveTypeOptions' => $driveTypeOptions,
            'fuelTypeOptions' => $fuelTypeOptions,
            'millageRanges' => $millageRanges,
        ];
    }

    public function paginated(array $filters, ?string $dealerId, array $capabilities): LengthAwarePaginator
    {
        $type = $filters['type'] ?? null;
        $branchId = $filters['branch_id'] ?? null;

        $query = Stock::query()
            ->select([
                'id',
                'branch_id',
                'is_active',
                'is_paid',
                'is_sold',
                'published_at',
                'internal_reference',
                'type',
                'name',
                'price',
                'discounted_price',
            ])
            ->selectRaw("
                EXISTS(
                    SELECT 1
                    FROM invoice_line_items ili
                    INNER JOIN invoices i ON i.id = ili.invoice_id
                    WHERE ili.stock_id = stock.id
                      AND i.deleted_at IS NULL
                      AND i.is_fully_paid = 1
                ) AS has_full_payment
            ")
            ->selectRaw("
                EXISTS(
                    SELECT 1
                    FROM invoice_line_items ili
                    INNER JOIN invoices i ON i.id = ili.invoice_id
                    INNER JOIN payments p ON p.invoice_id = i.id AND p.deleted_at IS NULL
                    WHERE ili.stock_id = stock.id
                      AND i.deleted_at IS NULL
                      AND i.is_fully_paid = 0
                ) AS has_partial_payment
            ")
            ->with([
                'branch:id,dealer_id,name',
                'branch.dealer:id,name,is_active',
                'vehicleItem:stock_id,make_id,model_id,is_import,condition,millage,color,gearbox_type,drive_type,fuel_type',
                'vehicleItem.make:id,name',
                'commercialItem:stock_id,make_id,color,condition,gearbox_type,fuel_type,millage',
                'commercialItem.make:id,name',
                'leisureItem:stock_id,make_id,color,condition',
                'leisureItem.make:id,name',
                'motorbikeItem:stock_id,make_id,color,condition,gearbox_type,fuel_type,millage',
                'motorbikeItem.make:id,name',
                'gearItem:stock_id,condition',
            ])
            ->withCount([
                'media as stock_images_count' => fn ($builder) => $builder->where('collection_name', 'stock_images'),
                'notes',
            ]);

        if ($dealerId) {
            $query->whereHas('branch', fn (Builder $builder) => $builder->where('dealer_id', $dealerId));
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        StockHelper::applyTypeAttributeFilters($query, $filters, $type, $capabilities);

        $this->applySorting(
            $query,
            $filters['sortBy'] ?? 'published_at',
            filter_var($filters['descending'] ?? false, FILTER_VALIDATE_BOOLEAN)
        );

        return $query->paginate((int) ($filters['rowsPerPage'] ?? 25))->appends($filters);
    }

    public function applySorting(Builder $query, ?string $sortBy, bool $descending): void
    {
        $direction = $descending ? 'desc' : 'asc';

        switch ($sortBy) {
            case 'dealer_name':
                $query->orderBy(
                    Dealer::query()
                        ->select('dealers.name')
                        ->join('dealer_branches', 'dealer_branches.dealer_id', '=', 'dealers.id')
                        ->whereColumn('dealer_branches.id', 'stock.branch_id')
                        ->limit(1),
                    $direction
                );
                break;
            case 'branch_name':
                $query->orderBy(
                    DealerBranch::query()
                        ->select('dealer_branches.name')
                        ->whereColumn('dealer_branches.id', 'stock.branch_id')
                        ->limit(1),
                    $direction
                );
                break;
            case 'active_label':
                $query->orderBy('is_active', $direction);
                break;
            case 'sold_label':
                $query->orderBy('is_sold', $direction);
                break;
            case 'type_title':
                $query->orderBy('type', $direction);
                break;
            case 'condition':
                $query->orderByRaw("
                    COALESCE(
                        (SELECT stv.condition FROM stock_type_vehicles stv WHERE stv.stock_id = stock.id LIMIT 1),
                        (SELECT stc.condition FROM stock_type_commercial stc WHERE stc.stock_id = stock.id LIMIT 1),
                        (SELECT stl.condition FROM stock_type_leisure stl WHERE stl.stock_id = stock.id LIMIT 1),
                        (SELECT stm.condition FROM stock_type_motorbikes stm WHERE stm.stock_id = stock.id LIMIT 1),
                        (SELECT stg.condition FROM stock_type_gear stg WHERE stg.stock_id = stock.id LIMIT 1)
                    ) {$direction}
                ");
                break;
            case 'notes_count':
                $query->orderBy('notes_count', $direction);
                break;
            case 'stock_images_count':
                $query->orderBy('stock_images_count', $direction);
                break;
            case 'make_name':
                $query->orderByRaw("
                    COALESCE(
                        (SELECT sm.name FROM stock_type_vehicles stv LEFT JOIN stock_makes sm ON sm.id = stv.make_id WHERE stv.stock_id = stock.id LIMIT 1),
                        (SELECT sm.name FROM stock_type_commercial stc LEFT JOIN stock_makes sm ON sm.id = stc.make_id WHERE stc.stock_id = stock.id LIMIT 1),
                        (SELECT sm.name FROM stock_type_leisure stl LEFT JOIN stock_makes sm ON sm.id = stl.make_id WHERE stl.stock_id = stock.id LIMIT 1),
                        (SELECT sm.name FROM stock_type_motorbikes stm LEFT JOIN stock_makes sm ON sm.id = stm.make_id WHERE stm.stock_id = stock.id LIMIT 1)
                    ) {$direction}
                ");
                break;
            case 'model_name':
                $query->orderByRaw("
                    COALESCE(
                        (SELECT sm.name FROM stock_type_vehicles stv LEFT JOIN stock_models sm ON sm.id = stv.model_id WHERE stv.stock_id = stock.id LIMIT 1),
                        ''
                    ) {$direction}
                ");
                break;
            case 'millage':
                $query->orderByRaw("
                    COALESCE(
                        (SELECT stv.millage FROM stock_type_vehicles stv WHERE stv.stock_id = stock.id LIMIT 1),
                        (SELECT stc.millage FROM stock_type_commercial stc WHERE stc.stock_id = stock.id LIMIT 1),
                        (SELECT stm.millage FROM stock_type_motorbikes stm WHERE stm.stock_id = stock.id LIMIT 1),
                        0
                    ) {$direction}
                ");
                break;
            case 'color_title':
                $query->orderByRaw("
                    COALESCE(
                        (SELECT stv.color FROM stock_type_vehicles stv WHERE stv.stock_id = stock.id LIMIT 1),
                        (SELECT stc.color FROM stock_type_commercial stc WHERE stc.stock_id = stock.id LIMIT 1),
                        (SELECT stl.color FROM stock_type_leisure stl WHERE stl.stock_id = stock.id LIMIT 1),
                        (SELECT stm.color FROM stock_type_motorbikes stm WHERE stm.stock_id = stock.id LIMIT 1),
                        ''
                    ) {$direction}
                ");
                break;
            case 'gearbox_type':
                $query->orderByRaw("
                    COALESCE(
                        (SELECT stv.gearbox_type FROM stock_type_vehicles stv WHERE stv.stock_id = stock.id LIMIT 1),
                        (SELECT stc.gearbox_type FROM stock_type_commercial stc WHERE stc.stock_id = stock.id LIMIT 1),
                        (SELECT stm.gearbox_type FROM stock_type_motorbikes stm WHERE stm.stock_id = stock.id LIMIT 1),
                        ''
                    ) {$direction}
                ");
                break;
            case 'drive_type':
                $query->orderByRaw("
                    COALESCE(
                        (SELECT stv.drive_type FROM stock_type_vehicles stv WHERE stv.stock_id = stock.id LIMIT 1),
                        ''
                    ) {$direction}
                ");
                break;
            case 'fuel_type':
                $query->orderByRaw("
                    COALESCE(
                        (SELECT stv.fuel_type FROM stock_type_vehicles stv WHERE stv.stock_id = stock.id LIMIT 1),
                        (SELECT stc.fuel_type FROM stock_type_commercial stc WHERE stc.stock_id = stock.id LIMIT 1),
                        (SELECT stm.fuel_type FROM stock_type_motorbikes stm WHERE stm.stock_id = stock.id LIMIT 1),
                        ''
                    ) {$direction}
                ");
                break;
            case 'is_import_label':
                $query->orderByRaw("
                    COALESCE(
                        (SELECT stv.is_import FROM stock_type_vehicles stv WHERE stv.stock_id = stock.id LIMIT 1),
                        0
                    ) {$direction}
                ");
                break;
            case 'name':
            case 'internal_reference':
            case 'price':
            case 'discounted_price':
            case 'published_at':
                $query->orderBy($sortBy, $direction);
                break;
            default:
                $query->orderBy('published_at', 'desc');
                break;
        }
    }

    public function typedModelClass(?string $type): ?string
    {
        return match ($type) {
            Stock::STOCK_TYPE_VEHICLE => StockTypeVehicle::class,
            Stock::STOCK_TYPE_MOTORBIKE => StockTypeMotorbike::class,
            Stock::STOCK_TYPE_LEISURE => StockTypeLeisure::class,
            Stock::STOCK_TYPE_COMMERCIAL => StockTypeCommercial::class,
            Stock::STOCK_TYPE_GEAR => StockTypeGear::class,
            default => null,
        };
    }
}
