<?php

namespace App\Actions\Backoffice\GuardBackoffice\System\Locations;
use Illuminate\Database\Eloquent\Model;

class DeleteLocationAction
{
    public function execute(Model $location): ?bool
    {
        return $location->delete();
    }
}
