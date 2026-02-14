<?php

namespace App\Actions\DealerManagement\Dealers;

use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerSalePerson;

class CreateDealerSalePersonAction
{
    public function execute(DealerBranch $branch, array $data): DealerSalePerson
    {
        return $branch->salePeople()->create([
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'contact_no' => $data['contact_no'],
            'email' => $data['email'] ?? null,
        ]);
    }
}
