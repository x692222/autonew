<?php

namespace App\Actions\Backoffice\Shared\Users;

use App\Models\System\User;

class DeleteBackofficeUserAction
{
    public function execute(User $user): void
    {
        $user->delete();
    }
}
