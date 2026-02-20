<?php

namespace App\Support\Customers;

use App\Models\Quotation\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CustomersIndexService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, ?string $dealerId = null): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $sortBy = (string) ($filters['sortBy'] ?? 'firstname');
        $descending = filter_var($filters['descending'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $direction = $descending ? 'desc' : 'asc';

        $query = Customer::query()
            ->withCount(['quotations', 'invoices', 'payments'])
            ->when(
                $dealerId,
                fn (Builder $builder) => $builder->where('dealer_id', $dealerId),
                fn (Builder $builder) => $builder->whereNull('dealer_id')
            )
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('firstname', 'like', '%' . $search . '%')
                        ->orWhere('lastname', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('contact_number', 'like', '%' . $search . '%')
                        ->orWhere('id_number', 'like', '%' . $search . '%');
                });
            })
            ->when($filters['type'] ?? null, fn (Builder $builder, string $type) => $builder->where('type', $type));

        $sortable = [
            'firstname',
            'lastname',
            'type',
            'email',
            'contact_number',
            'quotations_count',
            'invoices_count',
            'payments_count',
            'created_at',
        ];

        if (! in_array($sortBy, $sortable, true)) {
            $sortBy = 'firstname';
        }

        return $query
            ->orderBy($sortBy, $direction)
            ->paginate((int) ($filters['rowsPerPage'] ?? 10))
            ->appends($filters);
    }
}
