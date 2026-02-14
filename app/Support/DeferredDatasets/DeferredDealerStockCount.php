<?php

namespace App\Support\DeferredDatasets;

use App\Models\Dealer\DealerBranch;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class DeferredDealerStockCount
{
    public static function resolve(Collection $dealerIds, bool $isVisible, ?string $stockType = null)
    {
        return Inertia::defer(function () use ($isVisible, $dealerIds, $stockType) {
            if (!$isVisible) return null;
            if ($dealerIds->isEmpty()) return [];

            $q = DealerBranch::query()
                ->withoutGlobalScope(SoftDeletingScope::class)
                ->from('dealer_branches as db')
                ->whereNull('db.deleted_at')
                ->whereIn('db.dealer_id', $dealerIds)
                ->join('stock as s', function ($j) use ($stockType) {
                    $j->on('s.branch_id', '=', 'db.id')
                        ->whereNull('s.deleted_at');

                    if ($stockType) {
                        $j->where('s.type', $stockType);
                    }
                });

            return $q->selectRaw('
                    db.dealer_id as dealer_id,
                    SUM(CASE WHEN s.is_active = 1 THEN 1 ELSE 0 END) as active_stock_count,
                    SUM(CASE WHEN s.is_active = 0 THEN 1 ELSE 0 END) as inactive_stock_count,
                    COUNT(*) as total_stock_count,
                    SUM(CASE WHEN s.published_at IS NOT NULL THEN 1 ELSE 0 END) as published_count,
                    SUM(CASE WHEN s.published_at IS NULL THEN 1 ELSE 0 END) as unpublished_count
                ')
                ->groupBy('db.dealer_id')
                ->get()
                ->keyBy('dealer_id')
                ->map(fn ($r) => [
                    'active_stock_count' => (int)$r->active_stock_count,
                    'inactive_stock_count' => (int)$r->inactive_stock_count,
                    'total_stock_count' => (int)$r->total_stock_count,
                    'published_count' => (int)$r->published_count,
                    'unpublished_count' => (int)$r->unpublished_count,
                ])
                ->toArray();
        });
    }
}
