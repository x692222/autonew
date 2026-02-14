<?php

namespace App\Support\Lookups;

use App\Models\Stock\StockPublishLog;

class StockLookup
{
    public static function latestPublishedRecord(int $stockId): ?StockPublishLog
    {
        return StockPublishLog::query()
            ->where('stock_id', $stockId)
            ->where('action', '1')
            ->latest('created_at')
            ->first();
    }

    public static function canEditReference(int $stockId): bool
    {
        $latestPublish = self::latestPublishedRecord($stockId);

        if(empty($latestPublish)) {
            return true;
        }

        return !($latestPublish->exists && $latestPublish->created_at && now()->greaterThan($latestPublish->created_at->copy()->addHours(24)));
    }
}
