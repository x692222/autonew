<?php

namespace App\Support\Validation\Users;

use App\Models\Dealer\DealerUser;
use App\Models\System\User;
use Illuminate\Validation\Rule;

class AuthEmailUniquenessRules
{
    public function forSystemUsers(?User $ignoreUser = null): array
    {
        $usersUnique = Rule::unique('users', 'email');

        if ($ignoreUser !== null) {
            $usersUnique = $usersUnique->ignore($ignoreUser->getKey());
        }

        return [
            $usersUnique,
            Rule::unique('dealer_users', 'email')->whereNull('deleted_at'),
        ];
    }

    public function forDealerUsers(?DealerUser $ignoreDealerUser = null): array
    {
        $dealerUsersUnique = Rule::unique('dealer_users', 'email')->whereNull('deleted_at');

        if ($ignoreDealerUser !== null) {
            $dealerUsersUnique = $dealerUsersUnique->ignore($ignoreDealerUser->getKey());
        }

        return [
            $dealerUsersUnique,
            Rule::unique('users', 'email'),
        ];
    }
}
