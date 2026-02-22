<?php

namespace App\Support\Lookups;

use App\Models\Dealer\Dealer;
use App\Models\LineItem\StoredLineItem;
use App\Models\Quotation\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class QuotationLookupQueryService
{
    public function searchCustomers(?Dealer $dealer, string $query): Collection
    {
        return Customer::query()
            ->when(
                $dealer,
                fn ($builder) => $builder->where('dealer_id', $dealer->id),
                fn ($builder) => $builder->whereNull('dealer_id')
            )
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('firstname', 'like', '%' . $query . '%')
                    ->orWhere('lastname', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->orWhere('contact_number', 'like', '%' . $query . '%');
            })
            ->orderBy('firstname')
            ->limit(20)
            ->get();
    }

    public function quotationHistoryLineItems(?Dealer $dealer, string $section, string $query): Collection
    {
        $builder = StoredLineItem::query()
            ->when(
                $dealer,
                fn ($b) => $b->where('dealer_id', $dealer->id),
                fn ($b) => $b->whereNull('dealer_id')
            )
            ->where('section', $section)
            ->where('sku', 'like', '%' . $query . '%');

        if ($dealer && $section === 'general') {
            $this->excludeDealerStockLinkedSkus($builder, $dealer, 'stored_line_items');
        }

        return $builder
            ->latest('updated_at')
            ->limit(5)
            ->get();
    }

    public function invoiceHistoryLineItems(?Dealer $dealer, string $section, string $query): Collection
    {
        $builder = StoredLineItem::query()
            ->when(
                $dealer,
                fn ($b) => $b->where('dealer_id', $dealer->id),
                fn ($b) => $b->whereNull('dealer_id')
            )
            ->where('section', $section)
            ->where('sku', 'like', '%' . $query . '%');

        if ($dealer && $section === 'general') {
            $this->excludeDealerStockLinkedSkus($builder, $dealer, 'stored_line_items');
        }

        return $builder
            ->latest('updated_at')
            ->limit(5)
            ->get();
    }

    private function excludeDealerStockLinkedSkus(Builder $builder, Dealer $dealer, string $lineItemsTable): void
    {
        $builder->whereNotExists(function ($subQuery) use ($dealer, $lineItemsTable): void {
            $subQuery->selectRaw('1')
                ->from('stock')
                ->join('dealer_branches as db', 'db.id', '=', 'stock.branch_id')
                ->whereColumn('stock.internal_reference', $lineItemsTable . '.sku')
                ->where('db.dealer_id', $dealer->id);
        });
    }
}
