<?php

namespace Database\Seeders;

use App\Models\Dealer\DealerBranch;
use App\Models\Stock\Stock;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DealerBranchStockSeeder extends Seeder
{
    // Batch insert size (tweak if needed)
    private int $batchSize = 500;

    // Your enum types
    private array $types = ['vehicle', 'bakkie', 'motorbike', 'leisure', 'commercial', 'gear'];

    public function run(): void
    {
        $faker = Faker::create();

        $now = Carbon::now();

        DealerBranch::query()
            ->select(['id']) // only what we need
            ->whereNull('deleted_at')
            ->chunkById(200, function ($branches) use ($faker, $now) {
                foreach ($branches as $branch) {
                    $branchId = (int) $branch->id;

                    $count = random_int(100, 500);

                    // Build rows in batches to avoid massive memory usage
                    $buffer = [];
                    $bufferCount = 0;

                    // We want internal_reference unique PER branch.
                    // Easiest: deterministic incremental reference per branch.
                    for ($i = 1; $i <= $count; $i++) {
                        // ~70% active
                        $isActive = (random_int(1, 100) <= 70);

                        // Of the active: ~70% published in last 6 months
                        $publishedAt = null;
                        if ($isActive && random_int(1, 100) <= 70) {
                            $publishedAt = Carbon::instance(
                                $faker->dateTimeBetween($now->copy()->subMonths(6), $now)
                            );
                        }

                        $type = $this->types[array_rand($this->types)];

                        // Unique per branch:
                        $internalRef = 'BR' . $branchId . '-' . str_pad((string) $i, 6, '0', STR_PAD_LEFT);

                        $name = $faker->words(random_int(2, 5), true);

                        // Make slug globally unique (stock.slug is unique)
                        $slug = Str::slug($name . '-' . $internalRef);

                        $buffer[] = [
                            'branch_id'          => $branchId,
                            'is_active'          => $isActive ? 1 : 0,
                            'published_at'       => $publishedAt,
                            'internal_reference' => $internalRef,
                            'type'               => $type,
                            'name'               => Str::title($name),
                            'slug'               => $slug,
                            'created_at'         => $now,
                            'updated_at'         => $now,
                        ];

                        $bufferCount++;

                        if ($bufferCount >= $this->batchSize) {
                            Stock::query()->insert($buffer);
                            $buffer = [];
                            $bufferCount = 0;
                        }
                    }

                    // Flush remainder
                    if ($bufferCount > 0) {
                        Stock::query()->insert($buffer);
                    }
                }
            });
    }
}
