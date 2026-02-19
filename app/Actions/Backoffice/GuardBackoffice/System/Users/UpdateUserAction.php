<?php

namespace App\Actions\Backoffice\GuardBackoffice\System\Users;
use App\Models\System\User;

class UpdateUserAction
{
    public function execute(User $user, array $data): bool
    {
        $ok = $user->update([
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'email' => $data['email'],
        ]);

        $user->syncRoles([$data['role']]);

        return $ok;
    }
}
