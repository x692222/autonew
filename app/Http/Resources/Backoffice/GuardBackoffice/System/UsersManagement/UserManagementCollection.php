<?php

namespace App\Http\Resources\Backoffice\GuardBackoffice\System\UsersManagement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserManagementCollection extends ResourceCollection
{
    public $collects = UserManagementIndexResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
