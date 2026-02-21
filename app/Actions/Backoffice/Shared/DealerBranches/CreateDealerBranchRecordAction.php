<?php

namespace App\Actions\Backoffice\Shared\DealerBranches;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;

class CreateDealerBranchRecordAction
{
    public function execute(Dealer $dealer, array $data): DealerBranch
    {
        return $dealer->branches()->create($data);
    }
}
