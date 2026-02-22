<?php

namespace App\Support\LineItems;

use App\Models\Dealer\Dealer;
use App\Models\LineItem\StoredLineItem;

class StoredLineItemUpsertService
{
    public function upsert(?Dealer $dealer, array $lineItem): ?StoredLineItem
    {
        $sku = trim((string) ($lineItem['sku'] ?? ''));
        if ($sku === '') {
            return null;
        }

        return StoredLineItem::query()->updateOrCreate(
            [
                'scope_key' => StoredLineItem::scopeKeyFor($dealer?->id),
                'sku' => $sku,
            ],
            [
                'dealer_id' => $dealer?->id,
                'section' => (string) ($lineItem['section'] ?? ''),
                'description' => (string) ($lineItem['description'] ?? ''),
                'amount' => round((float) ($lineItem['amount'] ?? 0), 2),
            ]
        );
    }
}

