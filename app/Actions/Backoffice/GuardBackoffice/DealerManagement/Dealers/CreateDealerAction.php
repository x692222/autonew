<?php

namespace App\Actions\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Models\Dealer\Dealer;

class CreateDealerAction
{
    public function execute(array $data): Dealer
    {
        return Dealer::query()->create([
            'name' => $data['name'],
            'is_active' => true,
        ]);
    }
}
