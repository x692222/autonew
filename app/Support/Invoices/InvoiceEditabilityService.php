<?php

namespace App\Support\Invoices;

use App\Models\Dealer\Dealer;
use App\Models\Invoice\Invoice;
use App\Support\Settings\DealerSettingsResolver;
use App\Support\Settings\SystemSettingsResolver;
use App\Support\Payments\InvoicePaymentSummaryService;

class InvoiceEditabilityService
{
    public function __construct(
        private readonly InvoiceVatSnapshotResolver $vatSnapshotResolver,
        private readonly SystemSettingsResolver $systemSettingsResolver,
        private readonly DealerSettingsResolver $dealerSettingsResolver,
        private readonly InvoiceAmountSummaryService $amountSummaryService,
        private readonly InvoicePaymentSummaryService $paymentSummaryService,
    )
    {
    }

    public function systemCanEdit(Invoice $invoice): bool
    {
        $current = $this->vatSnapshotResolver->forSystem();
        $snapshot = [
            'vat_enabled' => (bool) $invoice->vat_enabled,
            'vat_percentage' => $invoice->vat_percentage !== null ? (float) $invoice->vat_percentage : null,
        ];

        if (! $this->vatSnapshotResolver->hasMatchingVatSnapshot($current, $snapshot)) {
            return false;
        }

        $settings = $this->systemSettingsResolver->resolve([
            'can_edit_invoice_after_partial_payment',
            'can_edit_invoice_after_full_payment',
        ]);

        return $this->isPaymentStateEditable(
            totalPaid: $this->paymentSummaryService->totalPaid($invoice),
            totalAmount: $this->amountSummaryService->totalForInvoice($invoice),
            allowAfterPartialPayment: (bool) ($settings['can_edit_invoice_after_partial_payment'] ?? false),
            allowAfterFullPayment: (bool) ($settings['can_edit_invoice_after_full_payment'] ?? false),
        );
    }

    public function dealerCanEdit(Invoice $invoice, Dealer $dealer): bool
    {
        $current = $this->vatSnapshotResolver->forDealer($dealer);
        $snapshot = [
            'vat_enabled' => (bool) $invoice->vat_enabled,
            'vat_percentage' => $invoice->vat_percentage !== null ? (float) $invoice->vat_percentage : null,
        ];

        if (! $this->vatSnapshotResolver->hasMatchingVatSnapshot($current, $snapshot)) {
            return false;
        }

        $settings = $this->dealerSettingsResolver->resolve($dealer->id, [
            'can_edit_invoice_after_partial_payment',
            'can_edit_invoice_after_full_payment',
        ]);

        return $this->isPaymentStateEditable(
            totalPaid: $this->paymentSummaryService->totalPaid($invoice),
            totalAmount: $this->amountSummaryService->totalForInvoice($invoice),
            allowAfterPartialPayment: (bool) ($settings['can_edit_invoice_after_partial_payment'] ?? false),
            allowAfterFullPayment: (bool) ($settings['can_edit_invoice_after_full_payment'] ?? false),
        );
    }

    public function canRecordPayment(Invoice $invoice): bool
    {
        return ! $this->paymentSummaryService->isFullyPaid($invoice);
    }

    private function isPaymentStateEditable(
        float $totalPaid,
        float $totalAmount,
        bool $allowAfterPartialPayment,
        bool $allowAfterFullPayment
    ): bool {
        if ($totalPaid <= 0) {
            return true;
        }

        $isFullyPaid = $totalAmount > 0 && $totalPaid >= $totalAmount;

        if ($isFullyPaid) {
            return $allowAfterFullPayment;
        }

        return $allowAfterPartialPayment;
    }
}
