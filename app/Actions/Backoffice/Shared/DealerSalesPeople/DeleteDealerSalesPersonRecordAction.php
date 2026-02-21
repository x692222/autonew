<?php

namespace App\Actions\Backoffice\Shared\DealerSalesPeople;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerSalePerson;
use App\Support\Security\TenantScopeEnforcer;

class DeleteDealerSalesPersonRecordAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Dealer $dealer, DealerSalePerson $salesPerson): void
    {
        $salesPerson->loadMissing('branch:id,dealer_id');
        $this->tenantScopeEnforcer->assertSameDealerScope($salesPerson->branch?->dealer_id, $dealer->id, 'branch_id');
        $salesPerson->delete();
    }
}
