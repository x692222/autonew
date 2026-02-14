<?php

namespace App\Policies\Backoffice\Auth;

use App\Models\Dealer\DealerUser;
use App\Models\System\User;
use Illuminate\Auth\Access\Response;

class ImpersonationsPolicy
{
    public function impersonate(User $user, DealerUser $dealerUser): Response
    {
        return $user->hasPermissionTo('impersonateDealershipUser', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to impersonate users.');
    }
}
