<?php

namespace App\Actions\DealerManagement\Dealers;

use App\Models\Dealer\Dealer;

class SetDealerActiveStatusAction
{
    public function execute(Dealer $dealer, bool $isActive): bool
    {
        return $dealer->update([
            'is_active' => $isActive,
        ]);
    }
}
