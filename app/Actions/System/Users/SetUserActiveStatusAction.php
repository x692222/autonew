<?php

namespace App\Actions\System\Users;

use App\Models\System\User;

class SetUserActiveStatusAction
{
    public function execute(User $user, bool $isActive): bool
    {
        return $user->update([
            'is_active' => $isActive,
        ]);
    }
}
