<?php

namespace App\Actions\Backoffice\GuardBackoffice\System\Locations;
use App\Support\Options\LocationOptions;
use Illuminate\Database\Eloquent\Model;

class DeleteLocationAction
{
    public function execute(Model $location): ?bool
    {
        $deleted = $location->delete();
        if ($deleted) {
            LocationOptions::bumpCacheVersion();
        }

        return $deleted;
    }
}
