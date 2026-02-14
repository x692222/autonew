<?php

namespace App\Support\Services;

use App\Models\Dealer\Dealer;
use App\Models\Leads\LeadPipeline;
use App\Models\Leads\LeadStage;
use Illuminate\Support\Facades\DB;

class DealerLeadDefaultsProvisioner
{
    public function provision(Dealer $dealer): void
    {
        DB::transaction(function () use ($dealer) {
            $defaults = $this->defaults();

            $pipelinesByName = [];

            foreach ($defaults['pipelines'] as $pipelineRow) {
                $name = (string)($pipelineRow['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                /** @var LeadPipeline|null $pipeline */
                $pipeline = LeadPipeline::withTrashed()
                    ->where('dealer_id', $dealer->getKey())
                    ->where('name', $name)
                    ->first();

                if (!$pipeline) {
                    $pipeline = new LeadPipeline();
                    $pipeline->dealer_id = (int)$dealer->getKey();
                    $pipeline->name = $name;
                }

                $pipeline->is_default = (bool)($pipelineRow['is_default'] ?? false);

                if ($pipeline->trashed()) {
                    $pipeline->restore();
                }

                $pipeline->save();

                $pipelinesByName[$name] = $pipeline;
            }

            // Ensure only ONE default pipeline per dealer
            $default = collect($pipelinesByName)->firstWhere(fn ($p) => (bool)$p->is_default);
            if ($default) {
                LeadPipeline::query()
                    ->where('dealer_id', $dealer->getKey())
                    ->where('id', '!=', $default->getKey())
                    ->update(['is_default' => 0]);
            }

            // ---------------------------
            // Stages
            // ---------------------------
            foreach ($defaults['stages'] as $stageRow) {
                $pipelineName = (string)($stageRow['pipeline'] ?? '');
                if ($pipelineName === '' || !isset($pipelinesByName[$pipelineName])) {
                    continue;
                }

                $pipeline = $pipelinesByName[$pipelineName];

                $stageName = (string)($stageRow['name'] ?? '');
                if ($stageName === '') {
                    continue;
                }

                /** @var LeadStage|null $stage */
                $stage = LeadStage::withTrashed()
                    ->where('pipeline_id', $pipeline->getKey())
                    ->where('name', $stageName)
                    ->first();

                if (!$stage) {
                    $stage = new LeadStage();
                    $stage->pipeline_id = (int)$pipeline->getKey();
                    $stage->name = $stageName;
                }

                $stage->sort_order = (int)($stageRow['sort_order'] ?? 0);

                $stage->is_terminal = (bool)($stageRow['is_terminal'] ?? false);
                $stage->is_won = (bool)($stageRow['is_won'] ?? false);
                $stage->is_lost = (bool)($stageRow['is_lost'] ?? false);

                $sla = $stageRow['sla_minutes_to_first_response'] ?? null;
                $stage->sla_minutes_to_first_response = ($sla === '' || $sla === null) ? null : (int)$sla;

                if ($stage->trashed()) {
                    $stage->restore();
                }

                $stage->save();
            }
        });
    }

    protected function defaults(): array
    {
        return [
            'pipelines' => [
                [
                    'name' => 'Finance & Approvals',
                    'is_default' => false,
                ],
                [
                    'name' => 'Test Drive & Follow-up',
                    'is_default' => false,
                ],
                [
                    'name' => 'Sales Pipeline',
                    'is_default' => true,
                ],
            ],

            'stages' => [
                [
                    'pipeline' => 'Sales Pipeline',
                    'name' => 'New Inquiry',
                    'sort_order' => 10,
                    'is_terminal' => false,
                    'is_won' => false,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Sales Pipeline',
                    'name' => 'Contacted',
                    'sort_order' => 20,
                    'is_terminal' => false,
                    'is_won' => false,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Sales Pipeline',
                    'name' => 'Qualified',
                    'sort_order' => 30,
                    'is_terminal' => false,
                    'is_won' => false,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Sales Pipeline',
                    'name' => 'Test Drive Scheduled',
                    'sort_order' => 40,
                    'is_terminal' => false,
                    'is_won' => false,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Sales Pipeline',
                    'name' => 'Offer Made',
                    'sort_order' => 50,
                    'is_terminal' => false,
                    'is_won' => false,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Sales Pipeline',
                    'name' => 'Negotiation',
                    'sort_order' => 60,
                    'is_terminal' => false,
                    'is_won' => false,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Sales Pipeline',
                    'name' => 'Sale Completed',
                    'sort_order' => 900,
                    'is_terminal' => true,
                    'is_won' => true,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Sales Pipeline',
                    'name' => 'Sale Lost',
                    'sort_order' => 910,
                    'is_terminal' => true,
                    'is_won' => false,
                    'is_lost' => true,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Test Drive & Follow-up',
                    'name' => 'Test Drive Scheduled',
                    'sort_order' => 10,
                    'is_terminal' => false,
                    'is_won' => false,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Test Drive & Follow-up',
                    'name' => 'Test Drive Completed',
                    'sort_order' => 20,
                    'is_terminal' => false,
                    'is_won' => false,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Test Drive & Follow-up',
                    'name' => 'Follow-up Call',
                    'sort_order' => 30,
                    'is_terminal' => false,
                    'is_won' => false,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Test Drive & Follow-up',
                    'name' => 'Customer Interested',
                    'sort_order' => 40,
                    'is_terminal' => false,
                    'is_won' => false,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Test Drive & Follow-up',
                    'name' => 'No Show',
                    'sort_order' => 900,
                    'is_terminal' => true,
                    'is_won' => false,
                    'is_lost' => true,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Finance & Approvals',
                    'name' => 'Finance Application Sent',
                    'sort_order' => 10,
                    'is_terminal' => false,
                    'is_won' => false,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Finance & Approvals',
                    'name' => 'Docs Received',
                    'sort_order' => 20,
                    'is_terminal' => false,
                    'is_won' => false,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Finance & Approvals',
                    'name' => 'Submitted to Bank',
                    'sort_order' => 30,
                    'is_terminal' => false,
                    'is_won' => false,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Finance & Approvals',
                    'name' => 'Approved',
                    'sort_order' => 900,
                    'is_terminal' => true,
                    'is_won' => true,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Finance & Approvals',
                    'name' => 'Declined',
                    'sort_order' => 910,
                    'is_terminal' => true,
                    'is_won' => false,
                    'is_lost' => true,
                    'sla_minutes_to_first_response' => null,
                ],
                [
                    'pipeline' => 'Finance & Approvals',
                    'name' => 'Pending',
                    'sort_order' => 40,
                    'is_terminal' => false,
                    'is_won' => false,
                    'is_lost' => false,
                    'sla_minutes_to_first_response' => null,
                ],
            ],
        ];
    }
}
