<?php

namespace App\Actions\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Models\Dealer\DealerUser;

class AssignDealerUserPermissionsAction
{
    public function execute(DealerUser $user, array $permissionNames): void
    {
        $user->syncPermissions($permissionNames);
    }
}
