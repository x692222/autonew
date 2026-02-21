<?php

namespace App\Actions\Backoffice\Shared\DealerUsers;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;

class CreateDealerUserRecordAction
{
    public function execute(Dealer $dealer, array $data): DealerUser
    {
        return $dealer->users()->create($data);
    }
}
