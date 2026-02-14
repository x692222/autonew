<?php

namespace App\Http\Resources\Backoffice\DealerManagement\Dealers\SalesPeople;

use App\Models\Dealer\DealerSalePerson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DealerSalePerson */
class DealerSalesPeopleIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $actor = $request->user('backoffice');
        $dealer = $this->branch?->dealer;

        return [
            'id' => $this->id,
            'branch' => $this->branch?->name ?? '-',
            'firstname' => $this->firstname ?? '-',
            'lastname' => $this->lastname ?? '-',
            'contact_no' => $this->contact_no ?? '-',
            'email' => $this->email ?? '-',
            'notes_count' => (int) ($this->notes_count ?? 0),
            'can' => [
                'edit' => $dealer ? ($actor?->can('updateSalesPerson', [$dealer, $this->resource]) ?? false) : false,
                'delete' => $dealer ? ($actor?->can('deleteSalesPerson', [$dealer, $this->resource]) ?? false) : false,
                'show_notes' => $dealer ? ($actor?->can('showNotes', $dealer) ?? false) : false,
            ],
        ];
    }
}
