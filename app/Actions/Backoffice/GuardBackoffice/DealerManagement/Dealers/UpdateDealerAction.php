<?php

namespace App\Actions\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Models\Dealer\Dealer;

class UpdateDealerAction
{
    public function execute(Dealer $dealer, array $data): bool
    {
        return $dealer->update([
            'name' => $data['name'],
        ]);
    }
}
