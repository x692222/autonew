<?php

namespace App\Actions\Backoffice\Auth;

use App\Models\System\User;
use Illuminate\Support\Facades\Hash;

class ResetBackofficeUserPasswordAction
{
    public function execute(User $user, string $password): bool
    {
        return $user->update([
            'password' => Hash::make($password),
        ]);
    }
}
