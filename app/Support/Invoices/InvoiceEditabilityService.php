<?php

namespace App\Support\Invoices;

use App\Models\Dealer\Dealer;
use App\Models\Invoice\Invoice;

class InvoiceEditabilityService
{
    public function __construct(private readonly InvoiceVatSnapshotResolver $vatSnapshotResolver)
    {
    }

    public function systemCanEdit(Invoice $invoice): bool
    {
        $current = $this->vatSnapshotResolver->forSystem();
        $snapshot = [
            'vat_enabled' => (bool) $invoice->vat_enabled,
            'vat_percentage' => $invoice->vat_percentage !== null ? (float) $invoice->vat_percentage : null,
        ];

        return $this->vatSnapshotResolver->hasMatchingVatSnapshot($current, $snapshot);
    }

    public function dealerCanEdit(Invoice $invoice, Dealer $dealer): bool
    {
        $current = $this->vatSnapshotResolver->forDealer($dealer);
        $snapshot = [
            'vat_enabled' => (bool) $invoice->vat_enabled,
            'vat_percentage' => $invoice->vat_percentage !== null ? (float) $invoice->vat_percentage : null,
        ];

        return $this->vatSnapshotResolver->hasMatchingVatSnapshot($current, $snapshot);
    }
}

