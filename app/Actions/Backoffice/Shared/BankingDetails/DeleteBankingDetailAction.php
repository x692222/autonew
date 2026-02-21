<?php

namespace App\Actions\Backoffice\Shared\BankingDetails;

use App\Models\Dealer\Dealer;
use App\Models\Billing\BankingDetail;
use App\Support\Security\TenantScopeEnforcer;

class DeleteBankingDetailAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(BankingDetail $bankingDetail, ?Dealer $dealer = null): void
    {
        $this->tenantScopeEnforcer->assertSameDealerScope($bankingDetail->dealer_id, $dealer?->id, 'banking_detail_id');
        $bankingDetail->delete();
    }
}
