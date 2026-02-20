<?php

namespace App\Actions\Backoffice\Shared\Payments;

use App\Models\Invoice\Invoice;
use App\Models\Payments\Payment;
use App\Support\Invoices\InvoiceAmountSummaryService;
use App\Support\Payments\InvoicePaymentStateUpdater;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpsertPaymentAction
{
    public function __construct(
        private readonly InvoicePaymentStateUpdater $invoicePaymentStateUpdater,
        private readonly InvoiceAmountSummaryService $amountSummaryService
    ) {
    }

    public function execute(?Payment $payment, Invoice $invoice, array $data, Model $actor, ?string $ipAddress = null): Payment
    {
        return DB::transaction(function () use ($payment, $invoice, $data, $actor, $ipAddress): Payment {
            $amount = round((float) ($data['amount'] ?? 0), 2);
            $existingTotal = round((float) $invoice->payments()->when($payment, fn ($query) => $query->where('id', '!=', $payment->id))->sum('amount'), 2);
            $wouldBeTotal = round($existingTotal + $amount, 2);
            $invoiceTotal = $this->amountSummaryService->totalForInvoice($invoice);

            if ($wouldBeTotal > $invoiceTotal) {
                throw ValidationException::withMessages([
                    'amount' => ['Payment amount exceeds the total invoice amount.'],
                ]);
            }

            $payload = [
                'invoice_id' => $invoice->id,
                'dealer_id' => $invoice->dealer_id,
                'banking_detail_id' => $data['payment_method'] === 'eft'
                    ? ($data['banking_detail_id'] ?? null)
                    : null,
                'payment_method' => $data['payment_method'],
                'amount' => $amount,
                'payment_date' => $data['payment_date'],
                'description' => $data['description'] ?: null,
                'is_approved' => false,
                'updated_by_type' => get_class($actor),
                'updated_by_id' => $actor->getKey(),
                'updated_from_ip' => $ipAddress,
            ];

            if (! $payment) {
                $payload['created_by_type'] = get_class($actor);
                $payload['created_by_id'] = $actor->getKey();
                $payload['created_from_ip'] = $ipAddress;
            }

            $payment = $payment
                ? tap($payment)->update($payload)
                : Payment::query()->create($payload);

            $this->invoicePaymentStateUpdater->recalculate($invoice->fresh());

            return $payment->fresh();
        });
    }
}
