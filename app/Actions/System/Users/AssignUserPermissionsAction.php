<?php

namespace App\Actions\System\Users;

use App\Models\System\User;

class AssignUserPermissionsAction
{
    public function execute(User $user, array $permissionNames): void
    {
        $user->syncPermissions($permissionNames);
    }
}
