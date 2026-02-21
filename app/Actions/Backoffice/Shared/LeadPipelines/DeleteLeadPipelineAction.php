<?php

namespace App\Actions\Backoffice\Shared\LeadPipelines;

use App\Models\Dealer\Dealer;
use App\Models\Leads\LeadPipeline;
use App\Support\Security\TenantScopeEnforcer;

class DeleteLeadPipelineAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Dealer $dealer, LeadPipeline $leadPipeline): void
    {
        $this->tenantScopeEnforcer->assertSameDealerScope($leadPipeline->dealer_id, $dealer->id, 'pipeline_id');
        $leadPipeline->delete();
    }
}
