<?php

namespace App\Actions\DealerManagement\Dealers;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;

class CreateDealerUserAction
{
    public function execute(Dealer $dealer, array $data): DealerUser
    {
        return $dealer->users()->create([
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'email' => $data['email'],
            'is_active' => true,
            'password' => null,
        ]);
    }
}
