<?php

namespace App\Support\DeferredDatasets;

use App\Models\Dealer\DealerBranch;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class DeferredBranchesCount
{
    public static function resolve(Collection $dealerIds, bool $isVisible)
    {
        return Inertia::defer(function() use ($isVisible, $dealerIds) {
            if (!$isVisible) return null;
            if ($dealerIds->isEmpty()) return [];

            return DealerBranch::query()
                ->whereIn('dealer_id', $dealerIds)
                ->selectRaw('dealer_id, COUNT(*) as branches_count')
                ->groupBy('dealer_id')
                ->get()
                ->keyBy('dealer_id')
                ->map(fn($r) => ['branches_count' => (int)$r->branches_count])
                ->toArray();
        });
    }
}
