<?php

namespace App\Actions\Backoffice\Shared\Stocks;

use App\Models\Dealer\Dealer;
use App\Models\Stock\Stock;
use App\Models\Stock\StockPublishLog;
use App\Support\Security\TenantScopeEnforcer;
use App\Support\StockHelper;
use Illuminate\Support\Facades\DB;

class CreateStockAction
{
    public function __construct(
        private readonly ResolveStockFeatureIdsAction $resolveStockFeatureIdsAction,
        private readonly TenantScopeEnforcer $tenantScopeEnforcer
    ) {}

    public function execute(Dealer $dealer, array $data, string $actorId, string $actorGuard): Stock
    {
        return DB::transaction(function () use ($dealer, $data, $actorId, $actorGuard) {
            $this->tenantScopeEnforcer->assertBranchInDealerScope((string) $data['branch_id'], $dealer);

            $type = (string) $data['type'];
            $typed = (array) ($data['typed'] ?? []);
            $meta = StockHelper::stockRelationMeta();

            $stock = Stock::query()->create([
                'branch_id' => (string) $data['branch_id'],
                'type' => $type,
                'name' => (string) $data['name'],
                'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
                'price' => (int) $data['price'],
                'discounted_price' => isset($data['discounted_price']) && $data['discounted_price'] !== '' ? (int) $data['discounted_price'] : null,
                'internal_reference' => $data['internal_reference'] ?? null,
                'published_at' => $data['published_at'] ?? null,
                'date_acquired' => $data['date_acquired'] ?? null,
                'is_sold' => false,
            ]);

            $typedClass = $meta[$type]['class'];
            $typedClass::query()->create([
                ...$typed,
                'stock_id' => (string) $stock->id,
            ]);

            $featureIds = $this->resolveStockFeatureIdsAction->execute($type, $data, $actorId, $actorGuard);
            if ($featureIds !== []) {
                $stock->features()->sync($featureIds);
            }

            if (! empty($data['published_at'])) {
                StockPublishLog::query()->create([
                    'stock_id' => (string) $stock->id,
                    'action' => StockPublishLog::ACTION_PUBLISH,
                    'by_user_id' => $actorId,
                ]);
            }

            return $stock;
        });
    }
}
