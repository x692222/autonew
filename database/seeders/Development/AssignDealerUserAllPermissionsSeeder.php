<?php

namespace Database\Seeders\Development;

use App\Models\Dealer\DealerUser;
use App\Actions\Backoffice\Shared\DealerUsers\AssignAllDealerPermissionsAction;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class AssignDealerUserAllPermissionsSeeder extends Seeder
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

        // Idempotent: re-running replaces direct permissions with the full dealer set.
        $count = app(AssignAllDealerPermissionsAction::class)->execute($user);

        $this->command?->info(sprintf(
            'Synced %d dealer permissions for %s.',
            $count,
            $user->email
        ));
    }
}
