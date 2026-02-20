<?php

namespace App\Support\Payments;

use App\Models\Payments\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PaymentsIndexService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, ?string $dealerId = null): LengthAwarePaginator
    {
        return Payment::query()
            ->when($dealerId, fn ($query) => $query->forDealer($dealerId), fn ($query) => $query->system())
            ->with(['invoice:id,invoice_identifier', 'invoice.lineItems:id,invoice_id,stock_id', 'createdBy'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                ->where(fn ($nested) => $nested
                    ->where('description', 'like', "%{$search}%")
                    ->orWhereHas('invoice', fn ($invoiceQ) => $invoiceQ->where('invoice_identifier', 'like', "%{$search}%"))))
            ->when($filters['invoice_identifier'] ?? null, fn ($query, $identifier) => $query
                ->whereHas('invoice', fn ($invoiceQ) => $invoiceQ->where('invoice_identifier', 'like', "%{$identifier}%")))
            ->when($filters['payment_method'] ?? null, fn ($query, $method) => $query->where('payment_method', $method))
            ->when($filters['payment_date_from'] ?? null, fn ($query, $from) => $query->whereDate('payment_date', '>=', $from))
            ->when($filters['payment_date_to'] ?? null, fn ($query, $to) => $query->whereDate('payment_date', '<=', $to))
            ->orderByDesc('payment_date')
            ->paginate((int) ($filters['rowsPerPage'] ?? 10))
            ->appends($filters);
    }

    /**
     * @param  callable(Payment):array<string, bool>  $abilityResolver
     * @return array<string, mixed>
     */
    public function toArray(Payment $payment, callable $abilityResolver): array
    {
        return [
            'id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'invoice_identifier' => $payment->invoice?->invoice_identifier,
            'payment_method' => Str::of($payment->payment_method?->value ?? (string) $payment->payment_method)
                ->replace('_', ' ')
                ->upper()
                ->toString(),
            'amount' => $payment->amount !== null ? (float) $payment->amount : null,
            'payment_date' => optional($payment->payment_date)?->format('Y-m-d'),
            'description' => $payment->description,
            'linked_stock_items_count' => (int) collect($payment->invoice?->lineItems ?? [])
                ->pluck('stock_id')
                ->filter()
                ->unique()
                ->count(),
            'recorded_by' => $payment->recordedByLabel(),
            'recorded_ip' => $payment->created_from_ip,
            'can' => $abilityResolver($payment),
        ];
    }
}
