<?php

namespace App\Http\Resources\Backoffice\DealerManagement\Dealers;

use App\Models\Dealer\Dealer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Dealer */
class DealerIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $actor = $request->user('backoffice');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => (bool) $this->is_active,
            'status' => $this->is_active ? 'Active' : 'Inactive',
            'branches_count' => (int) ($this->branches_count ?? 0),
            'users_count' => (int) ($this->users_count ?? 0),
            'notes_count' => (int) ($this->notes_count ?? 0),
            'can' => [
                'show' => $actor?->can('show', $this->resource) ?? false,
                'update' => $actor?->can('update', $this->resource) ?? false,
                'delete' => $actor?->can('delete', $this->resource) ?? false,
                'toggle_active' => $actor?->can('changeStatus', $this->resource) ?? false,
                'show_notes' => $actor?->can('showNotes', $this->resource) ?? false,
            ],
        ];
    }
}
