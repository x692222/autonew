<?php

namespace App\Support\Lookups;

use App\Http\Resources\Backoffice\Shared\QuotationLookups\LineItemHistorySuggestionCollection;
use App\Http\Resources\KeyValueOptions\CustomerLookupCollection;
use App\Http\Resources\KeyValueOptions\CustomerLookupCreatedResource;
use App\Http\Resources\KeyValueOptions\CustomerLookupResource;
use App\Models\Dealer\Dealer;
use App\Models\Quotation\Customer;
use App\Models\Stock\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class QuotationLookupFormattingService
{
    public function mapStockSuggestion(Stock $item): array
    {
        $typed = $this->typedStockItem($item);

        return [
            'source' => 'stock',
            'stock_id' => $item->id,
            'sku' => $item->internal_reference,
            'description' => $item->name ?: $item->internal_reference,
            'amount' => (float) $item->price,
            'qty' => 1,
            'total' => (float) $item->price,
            'meta' => collect([
                $item->type ? strtoupper((string) $item->type) : null,
                $typed?->condition ? 'Condition ' . strtoupper((string) $typed->condition) : null,
                $typed?->year_model ? 'Year ' . $typed->year_model : null,
                $typed?->millage ? 'Millage ' . $typed->millage : null,
                $typed?->make?->name ? 'Make ' . strtoupper((string) $typed->make->name) : null,
                $typed?->model?->name ? 'Model ' . strtoupper((string) $typed->model->name) : null,
                $item->is_active ? 'ACTIVE' : 'INACTIVE',
                $item->is_sold ? 'SOLD' : 'UNSOLD',
            ])->filter()->implode(' | '),
        ];
    }

    public function mapCustomerOption(Customer $customer): array
    {
        return (new CustomerLookupResource($customer))->resolve();
    }

    public function mapCustomerOptions(Collection $customers): array
    {
        return (new CustomerLookupCollection($customers))->resolve();
    }

    public function mapCreatedCustomer(Customer $customer): array
    {
        return (new CustomerLookupCreatedResource($customer))->resolve();
    }

    public function mapHistoryLineItems(Collection $lineItems): array
    {
        return (new LineItemHistorySuggestionCollection($lineItems))->resolve();
    }

    public function stockSuggestions(Dealer $dealer, string $query): array
    {
        return Stock::query()
            ->whereHas('branch', fn ($builder) => $builder->where('dealer_id', $dealer->id))
            ->with([
                'vehicleItem.make',
                'vehicleItem.model',
                'commercialItem.make',
                'commercialItem.model',
                'motorbikeItem.make',
                'motorbikeItem.model',
            ])
            ->where('is_active', true)
            ->where('is_sold', false)
            ->where('internal_reference', 'like', '%' . $query . '%')
            ->orderBy('internal_reference')
            ->limit(5)
            ->get()
            ->map(fn (Stock $item) => $this->mapStockSuggestion($item))
            ->values()
            ->all();
    }

    public function buildCustomerLabel(Customer $customer): string
    {
        return CustomerLookupResource::labelFor($customer);
    }

    private function typedStockItem(Stock $item): ?Model
    {
        return $item->vehicleItem
            ?? $item->commercialItem
            ?? $item->motorbikeItem;
    }
}
