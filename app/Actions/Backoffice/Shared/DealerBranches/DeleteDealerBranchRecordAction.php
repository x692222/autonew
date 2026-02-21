<?php

namespace App\Actions\Backoffice\Shared\DealerBranches;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Support\Security\TenantScopeEnforcer;

class DeleteDealerBranchRecordAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Dealer $dealer, DealerBranch $branch): void
    {
        $this->tenantScopeEnforcer->assertSameDealerScope($branch->dealer_id, $dealer->id, 'branch_id');
        $branch->delete();
    }
}
