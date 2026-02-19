<?php

namespace App\Support\Quotations;

use App\Enums\QuotationLineItemSectionEnum;
use App\Models\Quotation\Quotation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class QuotationIndexService
{
    public function paginate(
        Builder $query,
        array $filters,
        int $rowsPerPage = 25
    ): LengthAwarePaginator {
        $search = trim((string) ($filters['search'] ?? ''));
        $customer = trim((string) ($filters['customer'] ?? ''));

        $query
            ->with(['customer:id,firstname,lastname', 'dealer:id,name'])
            ->withCount(['notes'])
            ->withCount([
                'lineItems as total_items_general_accessories' => function (Builder $lineItems): void {
                    $lineItems->whereIn('section', [
                        QuotationLineItemSectionEnum::GENERAL->value,
                        QuotationLineItemSectionEnum::ACCESSORIES->value,
                    ]);
                },
            ])
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('quote_identifier', 'like', '%' . $search . '%')
                        ->orWhereHas('customer', function (Builder $customerQuery) use ($search): void {
                            $customerQuery
                                ->where('firstname', 'like', '%' . $search . '%')
                                ->orWhere('lastname', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%')
                                ->orWhere('contact_number', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($customer !== '', function (Builder $builder) use ($customer): void {
                $builder->whereHas('customer', function (Builder $customerQuery) use ($customer): void {
                    $customerQuery
                        ->where('firstname', 'like', '%' . $customer . '%')
                        ->orWhere('lastname', 'like', '%' . $customer . '%');
                });
            })
            ->when(($filters['quotation_date_from'] ?? null), fn (Builder $builder, string $date) => $builder->whereDate('quotation_date', '>=', $date))
            ->when(($filters['quotation_date_to'] ?? null), fn (Builder $builder, string $date) => $builder->whereDate('quotation_date', '<=', $date))
            ->when(($filters['valid_until_from'] ?? null), fn (Builder $builder, string $date) => $builder->whereDate('valid_until', '>=', $date))
            ->when(($filters['valid_until_to'] ?? null), fn (Builder $builder, string $date) => $builder->whereDate('valid_until', '<=', $date));

        $sortBy = (string) ($filters['sortBy'] ?? 'quotation_date');
        $direction = filter_var($filters['descending'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';

        match ($sortBy) {
            'quote_identifier' => $query->orderBy('quote_identifier', $direction),
            'total_items_general_accessories' => $query->orderBy('total_items_general_accessories', $direction),
            'valid_until' => $query->orderBy('valid_until', $direction),
            'customer_firstname' => $query->join('customers as customer_sort', 'customer_sort.id', '=', 'quotations.customer_id')->orderBy('customer_sort.firstname', $direction)->select('quotations.*'),
            'customer_lastname' => $query->join('customers as customer_sort_last', 'customer_sort_last.id', '=', 'quotations.customer_id')->orderBy('customer_sort_last.lastname', $direction)->select('quotations.*'),
            'total_amount' => $query->orderBy('total_amount', $direction),
            'created_at' => $query->orderBy('created_at', $direction),
            default => $query->orderBy('quotation_date', $direction),
        };

        return $query->paginate((int) ($filters['rowsPerPage'] ?? $rowsPerPage))->appends($filters);
    }
}

