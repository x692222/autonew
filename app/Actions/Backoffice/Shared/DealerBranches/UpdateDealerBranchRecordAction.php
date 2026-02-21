<?php

namespace App\Actions\Backoffice\Shared\DealerBranches;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Support\Security\TenantScopeEnforcer;

class UpdateDealerBranchRecordAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Dealer $dealer, DealerBranch $branch, array $data): void
    {
        $this->tenantScopeEnforcer->assertSameDealerScope($branch->dealer_id, $dealer->id, 'branch_id');
        $branch->update($data);
    }
}
