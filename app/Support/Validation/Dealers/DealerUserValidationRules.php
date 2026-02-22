<?php

namespace App\Support\Validation\Dealers;

use App\Models\Dealer\DealerUser;
use App\Support\Validation\Users\AuthEmailUniquenessRules;
use App\Support\Validation\Users\UserIdentityValidationRules;

class DealerUserValidationRules
{
    public function __construct(
        private readonly UserIdentityValidationRules $identityRules,
        private readonly AuthEmailUniquenessRules $authEmailUniquenessRules,
    )
    {
    }

    public function single(?DealerUser $dealerUser = null): array
    {
        return $this->identityRules->single(
            $this->authEmailUniquenessRules->forDealerUsers($dealerUser)
        );
    }

    public function many(string $root = 'dealer_users'): array
    {
        return array_merge(
            [
                $root => ['required', 'array', 'min:1'],
            ],
            $this->identityRules->many($root, $this->authEmailUniquenessRules->forDealerUsers())
        );
    }
}
