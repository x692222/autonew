<?php

namespace App\Support\Quotations;

use App\Models\Quotation\Quotation;
use Illuminate\Support\Facades\DB;

class QuotationIdentifierGenerator
{
    public function next(?string $dealerId): string
    {
        return (string) DB::transaction(function () use ($dealerId): int {
            $latest = Quotation::query()
                ->withTrashed()
                ->when($dealerId, fn ($query) => $query->where('dealer_id', $dealerId))
                ->when(!$dealerId, fn ($query) => $query->whereNull('dealer_id'))
                ->where('has_custom_quote_identifier', false)
                ->whereRaw("quote_identifier REGEXP '^[0-9]+$'")
                ->lockForUpdate()
                ->orderByRaw('CAST(quote_identifier AS UNSIGNED) DESC')
                ->value('quote_identifier');

            return ((int) ($latest ?? 0)) + 1;
        });
    }
}
