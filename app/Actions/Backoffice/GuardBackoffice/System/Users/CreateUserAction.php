<?php

namespace App\Actions\Backoffice\GuardBackoffice\System\Users;
use App\Models\System\User;

class CreateUserAction
{
    public function execute(array $data): User
    {
        return User::query()->create([
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'email' => $data['email'],
            'password' => $data['password'] ?? null,
        ]);
    }
}
