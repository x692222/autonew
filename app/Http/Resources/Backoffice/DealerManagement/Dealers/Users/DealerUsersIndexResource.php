<?php

namespace App\Http\Resources\Backoffice\DealerManagement\Dealers\Users;

use App\Models\Dealer\DealerUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DealerUser */
class DealerUsersIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $actor = $request->user('backoffice');
        $dealer = $this->dealer;

        return [
            'id' => $this->id,
            'name' => trim((string) $this->firstname . ' ' . (string) $this->lastname),
            'firstname' => $this->firstname ?? '-',
            'lastname' => $this->lastname ?? '-',
            'email' => $this->email ?? '-',
            'is_active' => (bool) $this->is_active,
            'status' => $this->is_active ? 'Active' : 'Inactive',
            'notes_count' => (int) ($this->notes_count ?? 0),
            'can' => [
                'edit' => $dealer ? ($actor?->can('updateDealerUser', [$dealer, $this->resource]) ?? false) : false,
                'delete' => $dealer ? ($actor?->can('deleteDealerUser', [$dealer, $this->resource]) ?? false) : false,
                'reset_password' => $dealer ? ($actor?->can('resetDealerUserPassword', [$dealer, $this->resource]) ?? false) : false,
                'assign_permissions' => $dealer ? ($actor?->can('assignDealerUserPermissions', [$dealer, $this->resource]) ?? false) : false,
                'show_notes' => $dealer ? ($actor?->can('showNotes', $dealer) ?? false) : false,
            ],
        ];
    }
}
