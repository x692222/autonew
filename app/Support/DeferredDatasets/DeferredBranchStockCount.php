<?php

namespace App\Support\DeferredDatasets;

use App\Models\Stock\Stock;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class DeferredBranchStockCount
{
    public static function resolve(Collection $branchIds, bool $isVisible, ?string $stockType = null)
    {
        return Inertia::defer(function () use ($isVisible, $branchIds, $stockType) {
            if (!$isVisible) return null;
            if ($branchIds->isEmpty()) return [];

            $q = Stock::query()
                ->withoutGlobalScope(SoftDeletingScope::class)
                ->whereNull('deleted_at')
                ->whereIn('branch_id', $branchIds);

            if ($stockType) {
                $q->where('type', $stockType);
            }

            return $q->selectRaw('
                    branch_id,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_stock_count,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_stock_count,
                    COUNT(*) as total_stock_count,
                    SUM(CASE WHEN published_at IS NOT NULL THEN 1 ELSE 0 END) as published_count,
                    SUM(CASE WHEN published_at IS NULL THEN 1 ELSE 0 END) as unpublished_count
                ')
                ->groupBy('branch_id')
                ->get()
                ->keyBy('branch_id')
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
