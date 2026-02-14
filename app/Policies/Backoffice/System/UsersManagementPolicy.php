<?php

namespace App\Policies\Backoffice\System;

use App\Models\System\User;
use Illuminate\Auth\Access\Response;

class UsersManagementPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('indexSystemUsers', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to view users.');
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('createSystemUsers', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to create users.');
    }

    public function update(User $user, User $target): Response
    {
        return $user->hasPermissionTo('editSystemUsers', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to edit users.');
    }

    public function delete(User $user, User $target): Response
    {
        if (! $user->hasPermissionTo('deleteSystemUsers', 'backoffice')) {
            return Response::deny('You do not have permission to delete users.');
        }

        if ((string) $user->getKey() === (string) $target->getKey()) {
            return Response::deny('You cannot delete your own account.');
        }

        return Response::allow();
    }

    public function resetPassword(User $user, User $target): Response
    {
        return $user->hasPermissionTo('resetSystemUserPasswords', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to reset passwords.');
    }

    public function assignPermissions(User $user, User $target): Response
    {
        return $user->hasPermissionTo('assignPermissions', 'backoffice')
            ? Response::allow()
            : Response::deny('You do not have permission to assign permissions.');
    }
}
