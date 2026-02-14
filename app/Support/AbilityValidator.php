<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\System\Permission;

class AbilityValidator
{
    /**
     * Flat list:
     * [
     *   "permission.name" => true/false,
     *   ...
     * ]
     */
    public function permissionsFor(?Authenticatable $user, ?string $guard = null): array
    {
        $guard ??= $this->resolveGuard($user);
        $permissionNames = Permission::query()
            ->when($guard, fn ($q) => $q->where('guard_name', $guard))
            ->orderBy('name')
            ->pluck('name')
            ->all();

        if (! $user) {
            return array_fill_keys($permissionNames, false);
        }

        $checker = $this->makeChecker($user, $guard);

        $out = [];
        foreach ($permissionNames as $name) {
            $out[$name] = $checker($name);
        }

        return $out;
    }

    private function resolveGuard(?Authenticatable $user): ?string
    {
        if ($user) {
            if (property_exists($user, 'guard_name') && ! empty($user->guard_name)) {
                return $user->guard_name;
            }

            if (method_exists($user, 'guardName')) {
                $g = $user->guardName();
                if (! empty($g)) return $g;
            }
        }

        return config('auth.defaults.guard');
    }

    private function makeChecker(Authenticatable $user, ?string $guard): callable
    {
        if (method_exists($user, 'hasPermissionTo')) {
            // Spatie API: hasPermissionTo($permission, $guardName = null)
            return fn (string $permission) => $user->hasPermissionTo($permission, $guard);
        }

        // Fallback to Laravel Gate ability checks
        return fn (string $permission) => $user->can($permission);
    }
}
