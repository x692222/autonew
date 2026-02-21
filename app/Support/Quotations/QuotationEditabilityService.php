<?php

namespace App\Support\Quotations;

use App\Models\Dealer\Dealer;
use App\Models\Quotation\Quotation;
use App\Support\Resolvers\Quotations\QuotationVatSnapshotResolver;

class QuotationEditabilityService
{
    public function __construct(private readonly QuotationVatSnapshotResolver $vatSnapshotResolver)
    {
    }

    public function systemCanEdit(Quotation $quotation): bool
    {
        $current = $this->vatSnapshotResolver->forSystem();
        $snapshot = [
            'vat_enabled' => (bool) $quotation->vat_enabled,
            'vat_percentage' => $quotation->vat_percentage !== null ? (float) $quotation->vat_percentage : null,
        ];

        return $this->vatSnapshotResolver->hasMatchingVatSnapshot($current, $snapshot);
    }

    public function dealerCanEdit(Quotation $quotation, Dealer $dealer): bool
    {
        $current = $this->vatSnapshotResolver->forDealer($dealer);
        $snapshot = [
            'vat_enabled' => (bool) $quotation->vat_enabled,
            'vat_percentage' => $quotation->vat_percentage !== null ? (float) $quotation->vat_percentage : null,
        ];

        return $this->vatSnapshotResolver->hasMatchingVatSnapshot($current, $snapshot);
    }
}
