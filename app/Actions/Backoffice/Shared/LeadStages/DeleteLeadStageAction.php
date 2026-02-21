<?php

namespace App\Actions\Backoffice\Shared\LeadStages;

use App\Models\Dealer\Dealer;
use App\Models\Leads\LeadStage;
use App\Support\Security\TenantScopeEnforcer;

class DeleteLeadStageAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Dealer $dealer, LeadStage $leadStage): void
    {
        $leadStage->loadMissing('pipeline:id,dealer_id');
        $this->tenantScopeEnforcer->assertSameDealerScope($leadStage->pipeline?->dealer_id, $dealer->id, 'stage_id');
        $leadStage->delete();
    }
}
