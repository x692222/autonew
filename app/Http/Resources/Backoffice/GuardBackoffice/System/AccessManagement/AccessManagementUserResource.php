<?php

namespace App\Http\Resources\Backoffice\GuardBackoffice\System\AccessManagement;
use App\Models\System\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class AccessManagementUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'name' => trim(sprintf('%s %s', (string) $this->firstname, (string) $this->lastname)),
            'email' => $this->email,
            'permissions' => $this->permissions->pluck('name')->values()->all(),
        ];
    }
}
