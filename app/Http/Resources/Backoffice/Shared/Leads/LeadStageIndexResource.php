<?php

namespace App\Http\Resources\Backoffice\Shared\Leads;
use App\Models\Leads\LeadStage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeadStage */
class LeadStageIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $context = (array) $request->attributes->get('lead_stage_context', []);

        return [
            'id' => (string) $this->id,
            'pipeline' => $this->pipeline?->name ?? '-',
            'pipeline_id' => (string) $this->pipeline_id,
            'name' => (string) $this->name,
            'sort_order' => (int) ($this->sort_order ?? 0),
            'is_terminal' => (bool) $this->is_terminal,
            'is_won' => (bool) $this->is_won,
            'is_lost' => (bool) $this->is_lost,
            'can' => [
                'edit' => (bool) ($context['can_edit'] ?? false),
                'delete' => (bool) ($context['can_delete'] ?? false),
            ],
        ];
    }
}
