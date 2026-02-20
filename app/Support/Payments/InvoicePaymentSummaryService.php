<?php

namespace App\Support\Payments;

use App\Models\Invoice\Invoice;
use Illuminate\Support\Collection;
use App\Support\Invoices\InvoiceAmountSummaryService;

class InvoicePaymentSummaryService
{
    public function __construct(
        private readonly InvoiceAmountSummaryService $amountSummaryService
    ) {
    }

    public function totalPaid(Invoice $invoice): float
    {
        if ($invoice->relationLoaded('payments')) {
            return round((float) $invoice->payments->sum('amount'), 2);
        }

        if (isset($invoice->paid_amount)) {
            return round((float) $invoice->paid_amount, 2);
        }

        return round((float) $invoice->payments()->sum('amount'), 2);
    }

    public function isFullyPaid(Invoice $invoice): bool
    {
        $invoiceTotal = $this->amountSummaryService->totalForInvoice($invoice);
        $totalPaid = $this->totalPaid($invoice);

        return $invoiceTotal > 0 && $totalPaid >= $invoiceTotal;
    }

    public function status(Invoice $invoice): string
    {
        $totalPaid = $this->totalPaid($invoice);

        if ($this->isFullyPaid($invoice)) {
            return 'full';
        }

        return $totalPaid > 0 ? 'partial' : 'none';
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     */
    public function totalPaidForInvoices(Collection $invoices): float
    {
        return round((float) $invoices->sum(fn (Invoice $invoice) => $this->totalPaid($invoice)), 2);
    }
}
