<?php

namespace App\Support;

use App\Models\Stock\Stock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class LiveWebsiteStockQuery
{
    /**
     * Apply "live on website" constraints to either an Eloquent Builder
     * or an Eloquent Relation (e.g. HasMany).
     */
    public static function apply(Builder|Relation $query): Builder
    {
        $builder = $query instanceof Relation ? $query->getQuery() : $query;

        return $builder
            ->where('stock.is_active', 1)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('is_sold', false)
            ->whereHas('branch.dealer', fn (Builder $q) => $q->where('is_active', true));
        // @todo reenable
        // ->withCount([
        //         'media as stock_images_count' => fn ($q) => $q->where('collection_name', 'stock_images'),
        //     ])
        //     ->whereHas('media', fn ($q) => $q->where('collection_name', 'stock_images'));
    }

    public static function query(): Builder
    {
        return self::apply(Stock::query());
    }
}
