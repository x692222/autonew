<?php

namespace App\Http\Resources\KeyValueOptions;

use App\Models\Quotation\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerLookupCreatedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Customer $customer */
        $customer = $this->resource;

        return [
            'id' => $customer->id,
            'label' => CustomerLookupResource::labelFor($customer),
            'type' => $customer->type?->value ?? (string) $customer->type,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'id_number' => $customer->id_number,
            'email' => $customer->email,
            'contact_number' => $customer->contact_number,
            'address' => $customer->address,
            'vat_number' => $customer->vat_number,
        ];
    }
}
