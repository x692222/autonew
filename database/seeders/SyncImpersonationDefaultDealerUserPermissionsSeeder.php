<?php

namespace Database\Seeders;

use App\Models\Dealer\DealerUser;
use App\Models\System\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class SyncImpersonationDefaultDealerUserPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = DealerUser::query()
            ->where('is_active', true)
            ->whereHas('dealer', fn ($query) => $query->where('is_active', true))
            ->orderBy('email')
            ->first();

        if (! $user) {
            $this->command?->warn('No active dealer user with an active dealer was found. No permissions were synced.');
            return;
        }

        $permissions = Permission::query()
            ->where('guard_name', 'dealer')
            ->get();

        // Idempotent: re-running replaces direct permissions with the full dealer set.
        $user->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info(sprintf(
            'Synced %d dealer permissions for %s.',
            $permissions->count(),
            $user->email
        ));
    }
}
