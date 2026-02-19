<?php

namespace App\Support\Invoices;

use App\Models\Invoice\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceIdentifierGenerator
{
    public function next(?string $dealerId): string
    {
        return (string) DB::transaction(function () use ($dealerId): int {
            $latest = Invoice::query()
                ->when($dealerId, fn ($query) => $query->where('dealer_id', $dealerId))
                ->when(!$dealerId, fn ($query) => $query->whereNull('dealer_id'))
                ->where('has_custom_invoice_identifier', false)
                ->whereRaw("invoice_identifier REGEXP '^[0-9]+$'")
                ->lockForUpdate()
                ->orderByRaw('CAST(invoice_identifier AS UNSIGNED) DESC')
                ->value('invoice_identifier');

            return ((int) ($latest ?? 0)) + 1;
        });
    }
}

