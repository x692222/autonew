<?php

namespace Database\Seeders;

use App\Models\System\Permission;
use App\Models\System\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class SyncBackofficeAdminPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::query()
            ->where('email', 'admin@localhost.com')
            ->first();

        if (! $user) {
            $this->command?->warn('User admin@localhost.com was not found. No permissions were synced.');
            return;
        }

        $permissions = Permission::query()
            ->where('guard_name', 'backoffice')
            ->pluck('name')
            ->all();

        // Re-running this keeps it idempotent: existing direct permissions are replaced.
        $user->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info(sprintf(
            'Synced %d backoffice permissions for %s.',
            count($permissions),
            $user->email
        ));
    }
}
