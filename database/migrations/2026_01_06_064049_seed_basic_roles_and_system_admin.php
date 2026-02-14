<?php

use App\Models\System\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {

            /*
             |---------------------------------------------------------
             | Create roles
             |---------------------------------------------------------
             */
            $roles = ['superadmin', 'admin', 'support'];

            foreach ($roles as $role) {
                Role::firstOrCreate([
                    'name' => $role,
                    'guard_name' => 'backoffice',
                ]);
            }

            /*
             |---------------------------------------------------------
             | Create system admin user
             |---------------------------------------------------------
             */
            $user = User::firstOrCreate(
                ['email' => 'admin@localhost.com'],
                [
                    'is_active'          => true,
                    'firstname'          => 'System',
                    'lastname'           => 'Admin',
                    'email_verified_at'  => Carbon::now(),
                    'password'           => Hash::make('password'),
                ]
            );

            /*
             |---------------------------------------------------------
             | Assign superadmin role
             |---------------------------------------------------------
             */
            if (!$user->hasRole('superadmin')) {
                $user->assignRole(Role::findByName('superadmin', 'backoffice'));
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {

            // Remove role from user
            if ($user = User::where('email', 'admin@localhost.com')->first()) {
                $user->removeRole(Role::findByName('superadmin', 'backoffice'));
                $user->delete();
            }

            // Remove roles
            Role::where('guard_name', 'backoffice')
                ->whereIn('name', ['superadmin', 'admin', 'support'])
                ->delete();
        });
    }
};

