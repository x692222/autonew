<?php

namespace App\Support\Validation\Dealers;

use App\Models\Dealer\DealerUser;
use Illuminate\Validation\Rule;

class DealerUserValidationRules
{
    public function single(?DealerUser $dealerUser = null): array
    {
        $emailRule = Rule::unique('dealer_users', 'email')->whereNull('deleted_at');
        if ($dealerUser !== null) {
            $emailRule = $emailRule->ignore($dealerUser->id);
        }

        return [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
        ];
    }

    public function many(string $root = 'dealer_users'): array
    {
        return [
            $root => ['required', 'array', 'min:1'],
            "{$root}.*.firstname" => ['required', 'string', 'max:255'],
            "{$root}.*.lastname" => ['required', 'string', 'max:255'],
            "{$root}.*.email" => [
                'required',
                'email',
                'max:255',
                Rule::unique('dealer_users', 'email')->whereNull('deleted_at'),
            ],
        ];
    }
}
