<?php

namespace App\Actions\Backoffice\Shared\LeadStages;

use App\Models\Dealer\Dealer;
use App\Models\Leads\LeadPipeline;
use App\Models\Leads\LeadStage;
use App\Support\Security\TenantScopeEnforcer;

class UpdateLeadStageAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Dealer $dealer, LeadStage $leadStage, array $data): void
    {
        $leadStage->loadMissing('pipeline:id,dealer_id');
        $this->tenantScopeEnforcer->assertSameDealerScope($leadStage->pipeline?->dealer_id, $dealer->id, 'stage_id');

        $pipelineDealerId = LeadPipeline::query()->whereKey((string) $data['pipeline_id'])->value('dealer_id');
        $this->tenantScopeEnforcer->assertSameDealerScope($pipelineDealerId, $dealer->id, 'pipeline_id');

        $leadStage->update($data);
    }
}
