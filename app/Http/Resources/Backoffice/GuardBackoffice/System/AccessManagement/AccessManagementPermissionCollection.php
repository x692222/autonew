<?php

namespace App\Http\Resources\Backoffice\GuardBackoffice\System\AccessManagement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AccessManagementPermissionCollection extends ResourceCollection
{
    public $collects = AccessManagementPermissionResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
