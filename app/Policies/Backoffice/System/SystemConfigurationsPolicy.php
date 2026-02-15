<?php

namespace App\Policies\Backoffice\System;

use App\Models\System\User;
use Illuminate\Auth\Access\Response;

class SystemConfigurationsPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('canConfigureSystemSettings', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view system settings.');
    }

    public function update(User $user): Response
    {
        return $user->hasPermissionTo('canConfigureSystemSettings', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to update system settings.');
    }
}
