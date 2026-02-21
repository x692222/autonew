<?php

namespace App\Support\Invoices;

use App\Models\Invoice\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class InvoiceIndexService
{
    public function __construct(
        private readonly InvoiceAmountSummaryService $amountSummaryService
    ) {
    }

    public function paginate(
        Builder $query,
        array $filters,
        int $rowsPerPage = 25
    ): LengthAwarePaginator {
        $search = trim((string) ($filters['search'] ?? ''));
        $customer = trim((string) ($filters['customer'] ?? ''));

        $query
            ->select('invoices.*')
            ->tap(fn (Builder $builder) => $this->amountSummaryService->applyComputedTotalAmount($builder))
            ->withSum('payments as paid_amount', 'amount')
            ->with(['customer:id,firstname,lastname', 'dealer:id,name'])
            ->withCount(['notes'])
            ->withCount([
                'payments as total_payments_count',
                'payments as verified_payments_count' => fn (Builder $payments) => $payments->where('is_approved', true),
            ])
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('invoice_identifier', 'like', '%' . $search . '%')
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
            ->when(($filters['invoice_date_from'] ?? null), fn (Builder $builder, string $date) => $builder->whereDate('invoice_date', '>=', $date))
            ->when(($filters['invoice_date_to'] ?? null), fn (Builder $builder, string $date) => $builder->whereDate('invoice_date', '<=', $date))
            ->when(($filters['payable_by_from'] ?? null), fn (Builder $builder, string $date) => $builder->whereDate('payable_by', '>=', $date))
            ->when(($filters['payable_by_to'] ?? null), fn (Builder $builder, string $date) => $builder->whereDate('payable_by', '<=', $date));

        $sortBy = (string) ($filters['sortBy'] ?? 'invoice_date');
        $direction = filter_var($filters['descending'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';

        match ($sortBy) {
            'invoice_identifier' => $query->orderBy('invoice_identifier', $direction),
            'payable_by' => $query->orderBy('payable_by', $direction),
            'customer_firstname' => $query->join('customers as customer_sort', 'customer_sort.id', '=', 'invoices.customer_id')->orderBy('customer_sort.firstname', $direction),
            'customer_lastname' => $query->join('customers as customer_sort_last', 'customer_sort_last.id', '=', 'invoices.customer_id')->orderBy('customer_sort_last.lastname', $direction),
            'total_amount' => $query->orderBy('total_amount', $direction),
            'total_paid_amount' => $query->orderBy('paid_amount', $direction),
            'total_due' => $query->orderByRaw("(COALESCE(total_amount, 0) - COALESCE(paid_amount, 0)) {$direction}"),
            'is_fully_paid' => $query->orderBy('is_fully_paid', $direction),
            'is_fully_verified' => $query->orderByRaw('(CASE WHEN total_payments_count > 0 AND total_payments_count = verified_payments_count THEN 1 ELSE 0 END) '.$direction),
            'created_at' => $query->orderBy('created_at', $direction),
            default => $query->orderBy('invoice_date', $direction),
        };

        return $query->paginate((int) ($filters['rowsPerPage'] ?? $rowsPerPage))->appends($filters);
    }
}
