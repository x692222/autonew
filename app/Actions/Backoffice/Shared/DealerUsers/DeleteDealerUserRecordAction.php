<?php

namespace App\Actions\Backoffice\Shared\DealerUsers;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use App\Support\Security\TenantScopeEnforcer;

class DeleteDealerUserRecordAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Dealer $dealer, DealerUser $dealerUser): void
    {
        $this->tenantScopeEnforcer->assertSameDealerScope($dealerUser->dealer_id, $dealer->id, 'dealer_user_id');
        $dealerUser->delete();
    }
}
