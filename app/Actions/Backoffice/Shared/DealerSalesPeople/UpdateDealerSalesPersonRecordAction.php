<?php

namespace App\Actions\Backoffice\Shared\DealerSalesPeople;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerSalePerson;
use App\Support\Security\TenantScopeEnforcer;

class UpdateDealerSalesPersonRecordAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Dealer $dealer, DealerSalePerson $salesPerson, array $data): void
    {
        $salesPerson->loadMissing('branch:id,dealer_id');
        $this->tenantScopeEnforcer->assertSameDealerScope($salesPerson->branch?->dealer_id, $dealer->id, 'branch_id');
        $this->tenantScopeEnforcer->assertBranchInDealerScope((string) $data['branch_id'], $dealer);
        $salesPerson->update($data);
    }
}
