<?php

namespace Database\Seeders;

use App\Models\Dealer\DealerBranch;
use App\Models\Stock\Stock;
use App\Models\Stock\StockFeatureTag;
use App\Models\Stock\StockMake;
use App\Models\Stock\StockModel;
use App\Models\Stock\StockTypeCommercial;
use App\Models\Stock\StockTypeGear;
use App\Models\Stock\StockTypeLeisure;
use App\Models\Stock\StockTypeMotorbike;
use App\Models\Stock\StockTypeVehicle;
use App\Models\Stock\StockView;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class StockSeeder extends Seeder
{
    private Carbon $publishStart;
    private Carbon $publishEnd;

    public function run(): void
    {
        $this->publishStart = Carbon::create(2025, 1, 1, 0, 0, 0);
        $this->publishEnd   = Carbon::create(2025, 12, 31, 23, 59, 59);

        // Allow creating with columns that may not be fillable in some models
        Model::unguard();

        // Preload reference data
        $makesByType = StockMake::query()
            ->select(['id', 'stock_type'])
            ->get()
            ->groupBy('stock_type');

        $modelsByMake = StockModel::query()
            ->select(['id', 'make_id'])
            ->get()
            ->groupBy('make_id');

        $featuresByType = StockFeatureTag::query()
            ->select(['id', 'stock_type'])
            ->get()
            ->groupBy('stock_type');

        $branches = DealerBranch::query()->select(['id'])->get();
        if ($branches->isEmpty()) {
            $this->command?->warn('No dealer branches found. Seeder skipped.');
            Model::reguard();
            return;
        }

        // We will insert type rows where the DB FKs are wrong (make_id points to stock.id in schema),
        // so disable FK checks for the seeding window.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($branches as $branch) {
            $this->seedBranchStock(
                $branch,
                $makesByType,
                $modelsByMake,
                $featuresByType
            );
        }

        // Build publish logs and views across ALL stock
        $this->seedStockPublishLogs();
        $this->seedStockViews();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        Model::reguard();
    }

    private function seedBranchStock(
        DealerBranch $branch,
                     $makesByType,
                     $modelsByMake,
                     $featuresByType
    ): void {
        $count = random_int(5, 50);

        // Exact per-branch counts (no drift)
        $activeCount   = (int) round($count * 0.70);
        $soldCount     = (int) round($count * 0.30);
        $publishedCount = (int) round($count * 0.70);
        $newCount      = (int) round($count * 0.35);

        $activeFlags = $this->shuffledBooleans($count, $activeCount);
        $soldFlags   = $this->shuffledBooleans($count, $soldCount);
        $pubFlags    = $this->shuffledBooleans($count, $publishedCount);
        $newFlags    = $this->shuffledBooleans($count, $newCount);

        $types = array_values(Stock::STOCK_TYPE_OPTIONS); // vehicle, motorbike, leisure, commercial, gear

        for ($i = 0; $i < $count; $i++) {
            $type = $types[array_rand($types)];

            // Make internal_reference unique within branch (constraint is (branch_id, internal_reference))
            $internalRef = $this->uniqueInternalReferenceForBranch($branch->id);

            $publishedAt = $pubFlags[$i]
                ? $this->randomDateTimeBetween($this->publishStart, $this->publishEnd)
                : null;

            $stock = $branch->stockItems()->create([
                'is_active'          => $activeFlags[$i],
                'is_sold'            => $soldFlags[$i],
                'published_at'       => $publishedAt,
                'internal_reference' => $internalRef,
                'type'               => $type,
                'name'               => Str::title($type) . " Item {$internalRef}",
                'slug'               => Str::slug($branch->id . '-' . $internalRef),
                'price'              => random_int(10000, 700000),
            ]);

            // Create exactly ONE child record matching type, via relationship
            $isNew = $newFlags[$i];
            $this->createTypeRecord($stock, $type, $isNew, $makesByType, $modelsByMake);

            // Assign features (7–15, type-filtered)
            $this->assignFeatures($stock, $type, $featuresByType);
        }
    }

    private function createTypeRecord(
        Stock $stock,
        string $type,
        bool $forceNewCondition,
        $makesByType,
        $modelsByMake
    ): void {
        if ($type === Stock::STOCK_TYPE_VEHICLE) {
            $make = $this->pickMake($makesByType, 'vehicle');
            $modelId = $make ? $this->pickModelId($modelsByMake, $make->id) : null;

            $condition = $forceNewCondition
                ? StockTypeVehicle::CONDITION_OPTION_NEW
                : StockTypeVehicle::CONDITION_OPTIONS[array_rand(StockTypeVehicle::CONDITION_OPTIONS)];

            $isImport = false;
            if ($condition === StockTypeVehicle::CONDITION_OPTION_USED) {
                $isImport = (bool) random_int(0, 1); // only if used
            }

            $year = random_int(1950, 2026);
            $millage = $this->vehicleMillage($condition, $year);

            $stock->vehicleItem()->create([
                'make_id'         => $make?->id ?? 1,
                'model_id'        => $modelId ?? 1,
                'is_import'       => $isImport,
                'year_model'      => $year,
                'category'        => StockTypeVehicle::CATEGORY_OPTIONS[array_rand(StockTypeVehicle::CATEGORY_OPTIONS)],
                'color'           => StockTypeVehicle::COLOR_OPTIONS[array_rand(StockTypeVehicle::COLOR_OPTIONS)],
                'condition'       => $condition,
                'gearbox_type'    => StockTypeVehicle::GEARBOX_TYPE_OPTIONS[array_rand(StockTypeVehicle::GEARBOX_TYPE_OPTIONS)],
                'fuel_type'       => StockTypeVehicle::FUEL_TYPE_OPTIONS[array_rand(StockTypeVehicle::FUEL_TYPE_OPTIONS)],
                'drive_type'      => StockTypeVehicle::DRIVE_TYPE_OPTIONS[array_rand(StockTypeVehicle::DRIVE_TYPE_OPTIONS)],
                'millage'         => $millage,
                'number_of_seats' => [2, 4, 5, 7][array_rand([2, 4, 5, 7])],
                'number_of_doors' => [2, 5][array_rand([2, 5])],
            ]);
            return;
        }

        if ($type === Stock::STOCK_TYPE_GEAR) {
            $condition = $forceNewCondition
                ? StockTypeGear::CONDITION_OPTION_NEW
                : StockTypeGear::CONDITION_OPTIONS[array_rand(StockTypeGear::CONDITION_OPTIONS)];

            $stock->gearItem()->create([
                'condition' => $condition,
            ]);
            return;
        }

        if ($type === Stock::STOCK_TYPE_LEISURE) {
            $make = $this->pickMake($makesByType, 'leisure');

            $condition = $forceNewCondition
                ? StockTypeLeisure::CONDITION_OPTION_NEW
                : StockTypeLeisure::CONDITION_OPTIONS[array_rand(StockTypeLeisure::CONDITION_OPTIONS)];

            $stock->leisureItem()->create([
                'make_id'    => $make?->id ?? 1,
                'year_model' => random_int(1950, 2026),
                'color'      => StockTypeLeisure::COLOR_OPTIONS[array_rand(StockTypeLeisure::COLOR_OPTIONS)],
                'condition'  => $condition,
            ]);
            return;
        }

        if ($type === Stock::STOCK_TYPE_COMMERCIAL) {
            $make = $this->pickMake($makesByType, 'commercial');

            $condition = $forceNewCondition
                ? StockTypeCommercial::CONDITION_OPTION_NEW
                : StockTypeCommercial::CONDITION_OPTIONS[array_rand(StockTypeCommercial::CONDITION_OPTIONS)];

            $year = random_int(1950, 2026);
            $millage = $this->commercialMillage($condition, $year);

            $stock->commercialItem()->create([
                'make_id'      => $make?->id ?? 1,
                'year_model'   => $year,
                'color'        => StockTypeCommercial::COLOR_OPTIONS[array_rand(StockTypeCommercial::COLOR_OPTIONS)],
                'condition'    => $condition,
                'gearbox_type' => StockTypeCommercial::GEARBOX_TYPE_OPTIONS[array_rand(StockTypeCommercial::GEARBOX_TYPE_OPTIONS)],
                'fuel_type'    => StockTypeCommercial::FUEL_TYPE_OPTIONS[array_rand(StockTypeCommercial::FUEL_TYPE_OPTIONS)],
                'millage'      => $millage,
            ]);
            return;
        }

        // motorbike
        if ($type === Stock::STOCK_TYPE_MOTORBIKE) {
            $make = $this->pickMake($makesByType, 'motorbike');

            $condition = $forceNewCondition
                ? StockTypeMotorbike::CONDITION_OPTION_NEW
                : StockTypeMotorbike::CONDITION_OPTIONS[array_rand(StockTypeMotorbike::CONDITION_OPTIONS)];

            $year = random_int(1950, 2026);
            $millage = $this->motorbikeMillage($condition, $year);

            // IMPORTANT:
            // - do NOT insert is_import (column does not exist, per your error)
            // - we only insert columns that exist in your StockTypeMotorbike fillable (and in DB schema)
            $stock->motorbikeItem()->create([
                'make_id'      => $make?->id ?? 1,
                'year_model'   => $year,
                'category'     => StockTypeMotorbike::CATEGORY_OPTIONS[array_rand(StockTypeMotorbike::CATEGORY_OPTIONS)],
                'color'        => StockTypeMotorbike::COLOR_OPTIONS[array_rand(StockTypeMotorbike::COLOR_OPTIONS)],
                'condition'    => $condition,
                'gearbox_type' => StockTypeMotorbike::GEARBOX_TYPE_OPTIONS[array_rand(StockTypeMotorbike::GEARBOX_TYPE_OPTIONS)],
                'fuel_type'    => StockTypeMotorbike::FUEL_TYPE_OPTIONS[array_rand(StockTypeMotorbike::FUEL_TYPE_OPTIONS)],
                'millage'      => $millage,
            ]);
            return;
        }
    }

    private function assignFeatures(Stock $stock, string $type, $featuresByType): void
    {
        $available = $featuresByType->get($type);
        if (!$available || $available->isEmpty()) {
            return;
        }

        $ids = $available->pluck('id')->all();
        $max = count($ids);

        $take = $max < 7 ? random_int(1, $max) : random_int(7, min(15, $max));

        shuffle($ids);
        $picked = array_slice($ids, 0, $take);

        $stock->features()->syncWithoutDetaching($picked);
    }

    private function seedStockPublishLogs(): void
    {
        $userId = DB::table('users')->inRandomOrder()->value('id') ?? 1;

        $publishedStockIds = DB::table('stock')
            ->whereNotNull('published_at')
            ->pluck('id')
            ->all();

        $unpublishedStockIds = DB::table('stock')
            ->whereNull('published_at')
            ->pluck('id')
            ->all();

        $rows = [];

        // All published_at get action=1
        foreach ($publishedStockIds as $sid) {
            $dt = $this->randomDateTimeBetween($this->publishStart, $this->publishEnd);
            $rows[] = [
                'stock_id'    => $sid,
                'action'      => '1',
                'by_user_id'  => $userId,
                'created_at'  => $dt,
                'updated_at'  => $dt,
                'deleted_at'  => null,
            ];
        }

        // For 30% of unpublished, insert 1 then 0 with increasing timestamps
        shuffle($unpublishedStockIds);
        $take = (int) round(count($unpublishedStockIds) * 0.30);
        $subset = array_slice($unpublishedStockIds, 0, $take);

        foreach ($subset as $sid) {
            $dt1 = $this->randomDateTimeBetween($this->publishStart, $this->publishEnd->copy()->subDays(1));
            $dt2 = $dt1->copy()->addMinutes(random_int(5, 60 * 24)); // must be after

            $rows[] = [
                'stock_id'    => $sid,
                'action'      => '1',
                'by_user_id'  => $userId,
                'created_at'  => $dt1,
                'updated_at'  => $dt1,
                'deleted_at'  => null,
            ];

            $rows[] = [
                'stock_id'    => $sid,
                'action'      => '0',
                'by_user_id'  => $userId,
                'created_at'  => $dt2,
                'updated_at'  => $dt2,
                'deleted_at'  => null,
            ];
        }

        $this->bulkInsert('stock_publish_logs', $rows, 2000);
    }

    private function seedStockViews(): void
    {
        $publishableStockIds = DB::table('stock_publish_logs')
            ->where('action', '1')
            ->distinct()
            ->pluck('stock_id')
            ->all();

        if (empty($publishableStockIds)) {
            return;
        }

        $countries = ['ZA', 'NA', 'US', 'BW', 'ZM'];

        // Cache is_sold values
        $soldMap = DB::table('stock')
            ->whereIn('id', $publishableStockIds)
            ->pluck('is_sold', 'id');

        // Insert per stock id, chunked
        foreach ($publishableStockIds as $sid) {
            $isSold = (bool) ($soldMap[$sid] ?? false);

            // DETAIL: 5 - 8000
            $detailCount = random_int(5, 100);
            $detailRows = [];
            for ($i = 0; $i < $detailCount; $i++) {
                $dt = $this->randomDateTimeBetween($this->publishStart, $this->publishEnd);
                $detailRows[] = [
                    'stock_id'    => $sid,
                    'is_sold'     => $isSold ? 1 : 0,
                    'ip_address'  => $this->randomIpv4(),
                    'type'        => StockView::VIEW_TYPE_DETAIL,
                    'country'     => $countries[array_rand($countries)],
                    'created_at'  => $dt,
                    'updated_at'  => $dt,
                ];
            }
            $this->bulkInsert('stock_views', $detailRows, 2000);

            // IMPRESSION: 1000 - 20000
            $impCount = random_int(100, 3000);
            $impRows = [];
            for ($i = 0; $i < $impCount; $i++) {
                $dt = $this->randomDateTimeBetween($this->publishStart, $this->publishEnd);
                $impRows[] = [
                    'stock_id'    => $sid,
                    'is_sold'     => $isSold ? 1 : 0,
                    'ip_address'  => $this->randomIpv4(),
                    'type'        => StockView::VIEW_TYPE_IMPRESSION,
                    'country'     => $countries[array_rand($countries)],
                    'created_at'  => $dt,
                    'updated_at'  => $dt,
                ];
            }
            $this->bulkInsert('stock_views', $impRows, 2000);
        }
    }

    private function bulkInsert(string $table, array $rows, int $chunkSize = 1000): void
    {
        if (empty($rows)) return;

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    private function shuffledBooleans(int $total, int $trueCount): array
    {
        $arr = array_fill(0, $total, false);
        for ($i = 0; $i < $trueCount; $i++) {
            $arr[$i] = true;
        }
        shuffle($arr);
        return $arr;
    }

    private function randomDateTimeBetween(Carbon $start, Carbon $end): Carbon
    {
        $ts = random_int($start->timestamp, $end->timestamp);
        return Carbon::createFromTimestamp($ts);
    }

    private function randomIpv4(): string
    {
        return implode('.', [
            random_int(1, 255),
            random_int(0, 255),
            random_int(0, 255),
            random_int(1, 254),
        ]);
    }

    private function uniqueInternalReferenceForBranch(int $branchId): string
    {
        // quick loop until unique under (branch_id, internal_reference)
        do {
            $ref = strtoupper(Str::random(3)) . '-' . random_int(10000, 99999);
            $exists = DB::table('stock')
                ->where('branch_id', $branchId)
                ->where('internal_reference', $ref)
                ->exists();
        } while ($exists);

        return $ref;
    }

    private function pickMake($makesByType, string $type): ?object
    {
        $bucket = $makesByType->get($type);
        if (!$bucket || $bucket->isEmpty()) return null;
        return $bucket[random_int(0, $bucket->count() - 1)];
    }

    private function pickModelId($modelsByMake, int $makeId): ?int
    {
        $bucket = $modelsByMake->get($makeId);
        if (!$bucket || $bucket->isEmpty()) return null;
        return $bucket[random_int(0, $bucket->count() - 1)]->id;
    }

    private function scaledMileage(int $year, int $min, int $max): int
    {
        $year = max(1950, min(2026, $year));
        $t = (2026 - $year) / (2026 - 1950); // 0 (new) -> 1 (old)
        $base = (int) round($min + $t * ($max - $min));

        $jitter = (int) round($base * (random_int(-10, 10) / 100));
        return max($min, min($max, $base + $jitter));
    }

    private function vehicleMillage(string $condition, int $year): int
    {
        if ($condition === StockTypeVehicle::CONDITION_OPTION_NEW) {
            return random_int(0, 50);
        }
        return $this->scaledMileage($year, 28000, 220000);
    }

    private function commercialMillage(string $condition, int $year): int
    {
        if ($condition === StockTypeCommercial::CONDITION_OPTION_NEW) {
            return random_int(0, 50);
        }
        return $this->scaledMileage($year, 28000, 220000);
    }

    private function motorbikeMillage(string $condition, int $year): int
    {
        if ($condition === StockTypeMotorbike::CONDITION_OPTION_NEW) {
            return random_int(0, 50);
        }
        return $this->scaledMileage($year, 3000, 60000);
    }
}
