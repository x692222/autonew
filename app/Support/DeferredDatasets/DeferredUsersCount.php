<?php

namespace App\Support\DeferredDatasets;

use App\Models\Dealer\DealerUser;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class DeferredUsersCount
{
    public static function resolve(Collection $dealerIds, bool $isVisible)
    {
        return Inertia::defer(function () use ($isVisible, $dealerIds) {
            if (!$isVisible) return null;
            if ($dealerIds->isEmpty()) return [];

            return DealerUser::query()
                ->whereIn('dealer_id', $dealerIds)
                ->selectRaw('
                    dealer_id,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users_count,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users_count
                ')
                ->groupBy('dealer_id')
                ->get()
                ->keyBy('dealer_id')
                ->map(fn ($r) => [
                    'active_users_count' => (int)$r->active_users_count,
                    'inactive_users_count' => (int)$r->inactive_users_count,
                ])
                ->toArray();
        });
    }
}
