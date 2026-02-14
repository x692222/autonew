<?php

namespace App\Actions\DealerManagement\Dealers;

use App\Models\Dealer\Dealer;

class DeleteDealerAction
{
    public function execute(Dealer $dealer): bool
    {
        return (bool) $dealer->delete();
    }
}
