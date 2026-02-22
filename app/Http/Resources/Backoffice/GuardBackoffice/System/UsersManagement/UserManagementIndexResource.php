<?php

namespace App\Http\Resources\Backoffice\GuardBackoffice\System\UsersManagement;
use App\Models\System\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserManagementIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $actor = $request->user('backoffice');
        $name = trim(sprintf('%s %s', (string) $this->firstname, (string) $this->lastname));

        return [
            'id' => $this->id,
            'name' => $name,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'is_active' => (bool) $this->is_active,
            'can' => [
                'update' => $actor?->can('update', $this->resource) ?? false,
                'delete' => $actor?->can('delete', $this->resource) ?? false,
                'toggle_active' => $actor?->can('update', $this->resource) ?? false,
                'reset_password' => $actor?->can('resetPassword', $this->resource) ?? false,
                'assign_permissions' => $actor?->can('assignPermissions', $this->resource) ?? false,
            ],
        ];
    }
}
