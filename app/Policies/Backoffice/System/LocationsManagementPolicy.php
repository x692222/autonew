<?php

namespace App\Policies\Backoffice\System;

use App\Models\System\User;
use Illuminate\Auth\Access\Response;

class LocationsManagementPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('indexSystemLocations', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view locations.');
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('createSystemLocations', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create locations.');
    }

    public function update(User $user, mixed $location): Response
    {
        return $user->hasPermissionTo('editSystemLocations', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit locations.');
    }

    public function delete(User $user, mixed $location): Response
    {
        return $user->hasPermissionTo('deleteSystemLocations', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to delete locations.');
    }
}
