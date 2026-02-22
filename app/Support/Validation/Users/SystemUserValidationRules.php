<?php

namespace App\Support\Validation\Users;

use App\Models\System\User;

class SystemUserValidationRules
{
    public function __construct(
        private readonly UserIdentityValidationRules $identityRules,
        private readonly AuthEmailUniquenessRules $authEmailUniquenessRules,
    )
    {
    }

    public function store(): array
    {
        return array_merge(
            $this->identityRules->single($this->authEmailUniquenessRules->forSystemUsers()),
            [
                'password' => ['nullable', 'string', 'min:8'],
            ]
        );
    }

    public function update(User $user): array
    {
        return $this->identityRules->single(
            $this->authEmailUniquenessRules->forSystemUsers($user)
        );
    }
}
