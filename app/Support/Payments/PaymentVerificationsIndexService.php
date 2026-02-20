<?php

namespace App\Support\Payments;

use App\Models\Payments\Payment;
use App\Support\Invoices\InvoiceSectionOptions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PaymentVerificationsIndexService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, ?string $dealerId = null): LengthAwarePaginator
    {
        $subtractive = "'" . implode("','", InvoiceSectionOptions::subtractiveValues()) . "'";
        $signedTotal = "(CASE WHEN ili.section IN ({$subtractive}) THEN -ili.total ELSE ili.total END)";
        $vatRate = "COALESCE(i.vat_percentage, 0)";
        $vatPortion = "(CASE WHEN i.vat_enabled = 1 AND {$vatRate} > 0 THEN ({$signedTotal} * {$vatRate} / (100 + {$vatRate})) ELSE 0 END)";
        $signedPayable = "(CASE WHEN ili.is_vat_exempt = 1 THEN ({$signedTotal} - {$vatPortion}) ELSE {$signedTotal} END)";

        return Payment::query()
            ->select('payments.*')
            ->selectRaw('(SELECT COUNT(*) FROM invoice_line_items ili WHERE ili.invoice_id = payments.invoice_id) as invoice_items_count')
            ->selectRaw("(SELECT ROUND(COALESCE(SUM({$signedPayable}), 0), 2) FROM invoice_line_items ili INNER JOIN invoices i ON i.id = ili.invoice_id WHERE ili.invoice_id = payments.invoice_id) as invoice_total_amount")
            ->when($dealerId, fn ($query) => $query->forDealer($dealerId), fn ($query) => $query->system())
            ->with([
                'invoice:id,invoice_identifier,invoice_date,customer_id',
                'invoice.customer:id,firstname,lastname',
                'invoice.lineItems:id,invoice_id,stock_id',
                'invoice.lineItems.stock:id,internal_reference',
                'latestVerification.verifiedBy',
            ])
            ->withCount('verifications')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                ->where(fn ($nested) => $nested
                    ->where('description', 'like', "%{$search}%")
                    ->orWhereHas('invoice', fn ($invoiceQ) => $invoiceQ->where('invoice_identifier', 'like', "%{$search}%"))))
            ->when($filters['invoice_identifier'] ?? null, fn ($query, $identifier) => $query
                ->whereHas('invoice', fn ($invoiceQ) => $invoiceQ->where('invoice_identifier', 'like', "%{$identifier}%")))
            ->when($filters['payment_method'] ?? null, fn ($query, $method) => $query->where('payment_method', $method))
            ->when(($filters['verification_status'] ?? 'pending') !== 'all', function ($query) use ($filters) {
                $isApproved = ($filters['verification_status'] ?? 'pending') === 'verified';
                $query->where('is_approved', $isApproved);
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
        $customerName = trim((string) (($payment->invoice?->customer?->firstname ?? '') . ' ' . ($payment->invoice?->customer?->lastname ?? '')));
        $stockNumbers = $payment->invoice?->lineItems
            ?->pluck('stock.internal_reference')
            ->filter()
            ->unique()
            ->values()
            ->all() ?? [];

        return [
            'id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'invoice_identifier' => (string) ($payment->invoice?->invoice_identifier ?? '-'),
            'invoice_date' => optional($payment->invoice?->invoice_date)?->format('Y-m-d'),
            'customer_name' => $customerName !== '' ? $customerName : '-',
            'stock_numbers' => $stockNumbers,
            'invoice_items_count' => (int) ($payment->invoice_items_count ?? 0),
            'invoice_total_amount' => $payment->invoice_total_amount !== null ? (float) $payment->invoice_total_amount : null,
            'payment_method' => Str::of($payment->payment_method?->value ?? (string) $payment->payment_method)
                ->replace('_', ' ')
                ->upper()
                ->toString(),
            'payment_amount' => $payment->amount !== null ? (float) $payment->amount : null,
            'payment_date' => optional($payment->payment_date)?->format('Y-m-d'),
            'is_approved' => (bool) $payment->is_approved,
            'verifications_count' => (int) ($payment->verifications_count ?? 0),
            'last_verified_at' => optional($payment->latestVerification?->date_verified)?->format('Y-m-d H:i:s'),
            'last_verified_by' => $payment->latestVerification?->verifiedByLabel(),
            'can' => $abilityResolver($payment),
        ];
    }
}
