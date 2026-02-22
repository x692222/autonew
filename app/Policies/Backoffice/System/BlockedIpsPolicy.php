<?php

namespace App\Policies\Backoffice\System;

use App\Models\Security\BlockedIp;
use App\Models\System\User;
use Illuminate\Auth\Access\Response;

class BlockedIpsPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('manageBlockedIps', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view blocked IPs.');
    }

    public function delete(User $user, BlockedIp $blockedIp): Response
    {
        return $user->hasPermissionTo('manageBlockedIps', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to unblock IPs.');
    }
}
