<?php

namespace App\Actions\Backoffice\Shared\DealerUsers;

use App\Models\Dealer\DealerUser;
use App\Models\System\Permission;
use Spatie\Permission\PermissionRegistrar;

class AssignAllDealerPermissionsAction
{
    public function execute(DealerUser $dealerUser): int
    {
        $permissions = Permission::query()
            ->where('guard_name', 'dealer')
            ->get();

        $dealerUser->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $permissions->count();
    }
}
