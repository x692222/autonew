<?php

namespace App\Support\Payments;

use App\Models\Invoice\Invoice;
use App\Models\Stock\Stock;
use App\Support\Invoices\InvoiceAmountSummaryService;

class InvoicePaymentStateUpdater
{
    public function __construct(
        private readonly InvoiceAmountSummaryService $amountSummaryService
    ) {
    }

    public function recalculate(Invoice $invoice): void
    {
        $invoice->loadMissing('payments');

        $totalPaid = round((float) $invoice->payments()->sum('amount'), 2);
        $invoiceTotal = $this->amountSummaryService->totalForInvoice($invoice);

        $invoice->update([
            'is_fully_paid' => $totalPaid >= $invoiceTotal && $invoiceTotal > 0,
        ]);

        $this->syncStockPaidStatus($invoice);
    }

    public function handleDeletedPayment(?Invoice $invoice, bool $wasFullyPaid): void
    {
        if (! $invoice) {
            return;
        }

        if (! $wasFullyPaid) {
            $this->recalculate($invoice);
            return;
        }

        $totalPaid = round((float) $invoice->payments()->sum('amount'), 2);
        $invoiceTotal = $this->amountSummaryService->totalForInvoice($invoice);

        $invoice->update([
            'is_fully_paid' => $totalPaid >= $invoiceTotal && $invoiceTotal > 0,
        ]);

        $stockIds = $invoice->lineItems()
            ->whereNotNull('stock_id')
            ->pluck('stock_id')
            ->filter()
            ->unique()
            ->values();

        if ($stockIds->isNotEmpty()) {
            Stock::query()
                ->whereIn('id', $stockIds)
                ->update(['is_paid' => false]);
        }
    }

    private function syncStockPaidStatus(Invoice $invoice): void
    {
        $stockIds = $invoice->lineItems()
            ->whereNotNull('stock_id')
            ->pluck('stock_id')
            ->filter()
            ->unique()
            ->values();

        foreach ($stockIds as $stockId) {
            $hasAnyFullyPaidInvoice = Invoice::query()
                ->whereNull('deleted_at')
                ->where('is_fully_paid', true)
                ->whereHas('lineItems', fn ($query) => $query->where('stock_id', $stockId))
                ->exists();

            Stock::query()
                ->where('id', $stockId)
                ->update(['is_paid' => $hasAnyFullyPaidInvoice]);
        }
    }
}
