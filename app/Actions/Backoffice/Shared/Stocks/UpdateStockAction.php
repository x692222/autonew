<?php

namespace App\Actions\Backoffice\Shared\Stocks;

use App\Models\Dealer\Dealer;
use App\Models\Stock\Stock;
use App\Models\Stock\StockPublishLog;
use App\Support\Security\TenantScopeEnforcer;
use App\Support\StockHelper;
use Illuminate\Support\Facades\DB;

class UpdateStockAction
{
    public function __construct(
        private readonly ResolveStockFeatureIdsAction $resolveStockFeatureIdsAction,
        private readonly TenantScopeEnforcer $tenantScopeEnforcer
    ) {}

    public function execute(Dealer $dealer, Stock $stock, array $data, string $actorId, string $actorGuard): Stock
    {
        return DB::transaction(function () use ($dealer, $stock, $data, $actorId, $actorGuard) {
            $this->tenantScopeEnforcer->assertStockInDealerScope($stock, $dealer);
            $this->tenantScopeEnforcer->assertBranchInDealerScope((string) $data['branch_id'], $dealer);

            $type = (string) $stock->type;
            $typed = (array) ($data['typed'] ?? []);
            $meta = StockHelper::stockRelationMeta();

            $wasPublished = ! empty($stock->published_at);
            $nowPublished = ! empty($data['published_at']);

            $stock->update([
                'branch_id' => (string) $data['branch_id'],
                'name' => (string) $data['name'],
                'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
                'price' => (int) $data['price'],
                'discounted_price' => isset($data['discounted_price']) && $data['discounted_price'] !== '' ? (int) $data['discounted_price'] : null,
                'internal_reference' => $data['internal_reference'] ?: $stock->internal_reference,
                'published_at' => $data['published_at'] ?? null,
                'date_acquired' => $data['date_acquired'] ?? null,
            ]);

            $typedRelation = $meta[$type]['relation'];
            $typedClass = $meta[$type]['class'];

            $typedRow = $stock->{$typedRelation}()->first();
            if (! $typedRow) {
                $typedRow = new $typedClass();
                $typedRow->stock_id = (string) $stock->id;
            }

            foreach ($typed as $key => $value) {
                $typedRow->{$key} = $value;
            }
            $typedRow->save();

            $featureIds = $this->resolveStockFeatureIdsAction->execute($type, $data, $actorId, $actorGuard);
            $stock->features()->sync($featureIds);

            if ($wasPublished !== $nowPublished) {
                StockPublishLog::query()->create([
                    'stock_id' => (string) $stock->id,
                    'action' => $nowPublished ? StockPublishLog::ACTION_PUBLISH : StockPublishLog::ACTION_UNPUBLISH,
                    'by_user_id' => $actorId,
                ]);
            }

            return $stock->fresh();
        });
    }
}
