<?php

namespace App\Actions\Backoffice\Shared\LeadStages;

use App\Models\Dealer\Dealer;
use App\Models\Leads\LeadPipeline;
use App\Models\Leads\LeadStage;
use App\Support\Security\TenantScopeEnforcer;

class CreateLeadStageAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Dealer $dealer, array $data): LeadStage
    {
        $pipelineDealerId = LeadPipeline::query()->whereKey((string) $data['pipeline_id'])->value('dealer_id');
        $this->tenantScopeEnforcer->assertSameDealerScope($pipelineDealerId, $dealer->id, 'pipeline_id');

        return LeadStage::query()->create($data);
    }
}
