<?php

namespace App\Actions\System\Users;

use App\Models\System\User;

class CreateUserAction
{
    public function execute(array $data): User
    {
        $user = User::query()->create([
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'email' => $data['email'],
            'password' => $data['password'] ?? null,
        ]);

        $user->syncRoles([$data['role']]);

        return $user;
    }
}
