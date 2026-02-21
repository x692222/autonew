<?php

namespace App\Actions\Backoffice\Shared\DealerUsers;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use App\Support\Security\TenantScopeEnforcer;

class UpdateDealerUserRecordAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Dealer $dealer, DealerUser $dealerUser, array $data): void
    {
        $this->tenantScopeEnforcer->assertSameDealerScope($dealerUser->dealer_id, $dealer->id, 'dealer_user_id');
        $dealerUser->update($data);
    }
}
