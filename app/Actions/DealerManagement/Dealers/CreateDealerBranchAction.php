<?php

namespace App\Actions\DealerManagement\Dealers;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;

class CreateDealerBranchAction
{
    public function execute(Dealer $dealer, array $data): DealerBranch
    {
        return $dealer->branches()->create([
            'suburb_id' => $data['suburb_id'],
            'name' => $data['name'],
            'contact_numbers' => $data['contact_numbers'],
            'display_address' => $data['display_address'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);
    }
}
