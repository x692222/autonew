<?php

namespace App\Support\Validation\Users;

class UserIdentityValidationRules
{
    public function single(array $emailRules = []): array
    {
        return [
            'firstname' => ['required', 'string', 'max:50'],
            'lastname' => ['required', 'string', 'max:50'],
            'email' => array_merge(['required', 'email', 'max:150'], $emailRules),
        ];
    }

    public function many(string $root, array $emailRules = []): array
    {
        return [
            "{$root}.*.firstname" => ['required', 'string', 'max:50'],
            "{$root}.*.lastname" => ['required', 'string', 'max:50'],
            "{$root}.*.email" => array_merge(['required', 'email', 'max:150'], $emailRules),
        ];
    }
}

