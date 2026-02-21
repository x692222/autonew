<?php

namespace App\Actions\Backoffice\Shared\DealerSalesPeople;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerSalePerson;
use App\Support\Security\TenantScopeEnforcer;

class CreateDealerSalesPersonRecordAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Dealer $dealer, array $data): DealerSalePerson
    {
        $this->tenantScopeEnforcer->assertBranchInDealerScope((string) $data['branch_id'], $dealer);
        return DealerSalePerson::query()->create($data);
    }
}
