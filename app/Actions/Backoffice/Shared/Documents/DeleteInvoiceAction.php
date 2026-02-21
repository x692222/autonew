<?php

namespace App\Actions\Backoffice\Shared\Documents;

use App\Models\Dealer\Dealer;
use App\Models\Invoice\Invoice;
use App\Support\Security\TenantScopeEnforcer;

class DeleteInvoiceAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Invoice $invoice, ?Dealer $dealer = null): void
    {
        $this->tenantScopeEnforcer->assertSameDealerScope($invoice->dealer_id, $dealer?->id, 'invoice');
        $invoice->delete();
    }
}
