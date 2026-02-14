<?php

namespace App\Actions\Backoffice\Auth;

use App\Models\Dealer\DealerUser;
use Illuminate\Support\Facades\Hash;

class ResetDealerUserPasswordAction
{
    public function execute(DealerUser $user, string $password): bool
    {
        return $user->update([
            'password' => Hash::make($password),
        ]);
    }
}
