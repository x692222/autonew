<?php

namespace App\Actions\Backoffice\Shared\Documents;

use App\Models\Dealer\Dealer;
use App\Models\Quotation\Quotation;
use App\Support\Security\TenantScopeEnforcer;

class DeleteQuotationAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Quotation $quotation, ?Dealer $dealer = null): void
    {
        $this->tenantScopeEnforcer->assertSameDealerScope($quotation->dealer_id, $dealer?->id, 'quotation');
        $quotation->delete();
    }
}
