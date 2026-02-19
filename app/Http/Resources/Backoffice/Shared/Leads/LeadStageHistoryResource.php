<?php

namespace App\Http\Resources\Backoffice\Shared\Leads;
use App\Models\Leads\LeadStageEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeadStageEvent */
class LeadStageHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'from' => (string) ($this->fromStage?->name ?? '-'),
            'to' => (string) ($this->toStage?->name ?? '-'),
            'changed_by' => $this->changedByDealerUser
                ? trim((string) $this->changedByDealerUser->firstname . ' ' . (string) $this->changedByDealerUser->lastname)
                : '-',
            'reason' => (string) ($this->reason ?? ''),
            'meta' => $this->meta,
            'created_at' => optional($this->created_at)?->format('Y-m-d H:i'),
        ];
    }
}
