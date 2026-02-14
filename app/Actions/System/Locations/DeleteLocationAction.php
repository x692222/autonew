<?php

namespace App\Actions\System\Locations;

use Illuminate\Database\Eloquent\Model;

class DeleteLocationAction
{
    public function execute(Model $location): ?bool
    {
        return $location->delete();
    }
}
