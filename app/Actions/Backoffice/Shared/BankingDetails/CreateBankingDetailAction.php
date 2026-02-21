<?php

namespace App\Actions\Backoffice\Shared\BankingDetails;

use App\Models\Dealer\Dealer;
use App\Models\Billing\BankingDetail;
use App\Support\Security\TenantScopeEnforcer;

class CreateBankingDetailAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(array $data, ?Dealer $dealer = null): BankingDetail
    {
        $this->tenantScopeEnforcer->assertSameDealerScope($data['dealer_id'] ?? null, $dealer?->id, 'dealer_id');
        return BankingDetail::query()->create($data);
    }
}
