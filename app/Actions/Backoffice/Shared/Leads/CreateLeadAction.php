<?php

namespace App\Actions\Backoffice\Shared\Leads;

use App\Models\Dealer\Dealer;
use App\Models\Leads\Lead;
use App\Support\Security\TenantScopeEnforcer;

class CreateLeadAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Dealer $dealer, array $data): Lead
    {
        $this->tenantScopeEnforcer->assertSameDealerScope($data['dealer_id'] ?? null, $dealer->id, 'dealer_id');
        return Lead::query()->create($data);
    }
}
