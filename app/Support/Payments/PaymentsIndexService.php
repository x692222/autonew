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
            ->with(['invoice:id,invoice_identifier', 'invoice.lineItems:id,invoice_id,stock_id', 'createdBy', 'bankingDetail:id,bank,account_number', 'latestVerification'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                ->where(fn ($nested) => $nested
                    ->where('description', 'like', "%{$search}%")
                    ->orWhere('created_from_ip', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhereRaw('CAST(amount AS CHAR) like ?', ["%{$search}%"])
                    ->orWhereHas('invoice', fn ($invoiceQ) => $invoiceQ->where('invoice_identifier', 'like', "%{$search}%"))))
            ->when($filters['payment_method'] ?? null, fn ($query, $method) => $query->where('payment_method', $method))
            ->when($filters['verification_status'] ?? null, function ($query, $status) {
                $resolved = $status === 'unverified' ? 'pending' : $status;

                if ($resolved === 'verified') {
                    $query->where('is_approved', true);
                    return;
                }

                if ($resolved === 'pending') {
                    $query->where('is_approved', false);
                }
            })
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
            'banking_detail_id' => $payment->banking_detail_id,
            'banking_detail_bank_account' => trim((string) (($payment->bankingDetail?->bank ?? '') . ' ' . ($payment->bankingDetail?->account_number ?? ''))),
            'payment_method_value' => (string) ($payment->payment_method?->value ?? $payment->payment_method),
            'payment_method' => Str::of($payment->payment_method?->value ?? (string) $payment->payment_method)
                ->replace('_', ' ')
                ->upper()
                ->toString(),
            'amount' => $payment->amount !== null ? (float) $payment->amount : null,
            'payment_date' => optional($payment->payment_date)?->format('Y-m-d'),
            'description' => $payment->description,
            'is_approved' => (bool) $payment->is_approved,
            'last_verified_at' => optional($payment->latestVerification?->date_verified)?->format('Y-m-d H:i:s'),
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
