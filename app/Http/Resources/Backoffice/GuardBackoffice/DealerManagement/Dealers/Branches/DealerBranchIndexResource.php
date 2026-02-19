<?php

namespace App\Http\Resources\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches;
use App\Models\Dealer\DealerBranch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DealerBranch */
class DealerBranchIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $actor = $request->user('backoffice');
        $dealer = $this->dealer;
        $suburb = $this->suburb;
        $city = $suburb?->city;
        $state = $city?->state;
        $country = $state?->country;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'country' => $country?->name ?? '-',
            'state' => $state?->name ?? '-',
            'city' => $city?->name ?? '-',
            'suburb' => $suburb?->name ?? '-',
            'sale_people_count' => (int) ($this->sale_people_count ?? 0),
            'notes_count' => (int) ($this->notes_count ?? 0),
            'can' => [
                'edit' => $actor?->can('updateBranch', [$dealer, $this->resource]) ?? false,
                'delete' => $actor?->can('deleteBranch', [$dealer, $this->resource]) ?? false,
                'show_notes' => $actor?->can('showNotes', $dealer) ?? false,
            ],
        ];
    }
}
