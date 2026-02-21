<?php

namespace App\Http\Resources\KeyValueOptions;

use App\Models\Quotation\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerLookupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Customer $customer */
        $customer = $this->resource;

        return [
            'value' => $customer->id,
            'label' => self::labelFor($customer),
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

    public static function labelFor(Customer $customer): string
    {
        $name = trim(($customer->firstname ?? '') . ' ' . ($customer->lastname ?? ''));
        $type = strtoupper((string) ($customer->type?->value ?? $customer->type ?? '-'));
        $parts = collect([
            $customer->email ? 'Email: ' . $customer->email : null,
            $customer->contact_number ? 'Contact: ' . $customer->contact_number : null,
            $customer->id_number ? 'ID: ' . $customer->id_number : null,
            'Type: ' . $type,
        ])->filter()->implode(' | ');

        return $parts !== '' ? $name . ' (' . $parts . ')' : $name;
    }
}
