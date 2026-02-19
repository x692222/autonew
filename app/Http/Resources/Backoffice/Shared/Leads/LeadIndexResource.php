<?php

namespace App\Http\Resources\Backoffice\Shared\Leads;
use App\Models\Leads\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/** @mixin Lead */
class LeadIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $context = (array) $request->attributes->get('lead_context', []);

        $canManage = (bool) ($context['can_manage'] ?? false);
        $canShowNotes = (bool) ($context['can_show_notes'] ?? false);

        $stock = $this->relationLoaded('stockItems') ? $this->stockItems->first() : null;

        return [
            'id' => (string) $this->id,
            'dealer_name' => $this->dealer?->name ?? '-',
            'branch' => $this->branch?->name ?? '-',
            'assigned_to' => $this->assignedToDealerUser
                ? trim((string) $this->assignedToDealerUser->firstname . ' ' . (string) $this->assignedToDealerUser->lastname)
                : '-',
            'pipeline' => $this->pipeline?->name ?? '-',
            'stage' => $this->stage?->name ?? '-',
            'firstname' => $this->firstname ?? '-',
            'lastname' => $this->lastname ?? '-',
            'email' => $this->email ?? '-',
            'contact_no' => $this->contact_no ?? '-',
            'lead_status' => $this->status ? Str::headline((string) $this->status) : '-',
            'source' => $this->source ? Str::headline((string) $this->source) : '-',
            'stock_name' => $stock?->name ?? '-',
            'stock_is_live' => $stock ? (bool) $stock->isLive($stock) : null,
            'notes_count' => (int) ($this->notes_count ?? 0),
            'conversations_count' => (int) ($this->conversations_count ?? 0),
            'created_date' => optional($this->created_at)?->format('Y-m-d H:i'),
            'can' => [
                'view' => $canManage,
                'edit' => $canManage,
                'delete' => $canManage,
                'show_notes' => $canShowNotes,
            ],
        ];
    }
}
