<?php

namespace App\Actions\Backoffice\GuardBackoffice\System\Users;
use App\Models\System\User;

class AssignUserPermissionsAction
{
    public function execute(User $user, array $permissionNames): void
    {
        $user->syncPermissions($permissionNames);
    }
}
