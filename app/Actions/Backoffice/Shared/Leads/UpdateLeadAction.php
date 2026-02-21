<?php

namespace App\Actions\Backoffice\Shared\Leads;

use App\Models\Dealer\Dealer;
use App\Models\Leads\Lead;
use App\Support\Security\TenantScopeEnforcer;

class UpdateLeadAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Dealer $dealer, Lead $lead, array $data): void
    {
        $this->tenantScopeEnforcer->assertSameDealerScope($lead->dealer_id, $dealer->id, 'lead_id');
        $lead->update($data);
    }
}
