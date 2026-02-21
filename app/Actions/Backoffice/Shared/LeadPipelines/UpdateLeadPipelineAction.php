<?php

namespace App\Actions\Backoffice\Shared\LeadPipelines;

use App\Models\Dealer\Dealer;
use App\Models\Leads\LeadPipeline;
use App\Support\Security\TenantScopeEnforcer;

class UpdateLeadPipelineAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Dealer $dealer, LeadPipeline $leadPipeline, array $data): void
    {
        $this->tenantScopeEnforcer->assertSameDealerScope($leadPipeline->dealer_id, $dealer->id, 'pipeline_id');

        if (($data['is_default'] ?? false) === true) {
            $dealer->pipelines()->update(['is_default' => false]);
        }

        $leadPipeline->update($data);
    }
}
