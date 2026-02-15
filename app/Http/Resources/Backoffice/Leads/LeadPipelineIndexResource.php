<?php

namespace App\Http\Resources\Backoffice\Leads;

use App\Models\Leads\LeadPipeline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeadPipeline */
class LeadPipelineIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $context = (array) $request->attributes->get('lead_pipeline_context', []);

        return [
            'id' => (string) $this->id,
            'name' => (string) $this->name,
            'is_default' => (bool) $this->is_default,
            'stages_count' => (int) ($this->stages_count ?? 0),
            'can' => [
                'edit' => (bool) ($context['can_edit'] ?? false),
                'delete' => (bool) ($context['can_delete'] ?? false),
            ],
        ];
    }
}
