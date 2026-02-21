<?php

namespace App\Support\Validation\Dealers;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerSalePerson;
use Illuminate\Validation\Rule;

class DealerSalesPersonValidationRules
{
    public function singleForDealer(Dealer $dealer, ?DealerSalePerson $salesPerson = null): array
    {
        $contactNoUnique = Rule::unique('dealer_sale_people', 'contact_no')->whereNull('deleted_at');
        $emailUnique = Rule::unique('dealer_sale_people', 'email')->whereNull('deleted_at');

        if ($salesPerson !== null) {
            $contactNoUnique = $contactNoUnique->ignore($salesPerson->id);
            $emailUnique = $emailUnique->ignore($salesPerson->id);
        }

        return [
            'branch_id' => ['required', 'string', Rule::exists(DealerBranch::class, 'id')->where('dealer_id', (string) $dealer->id)],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'contact_no' => ['required', 'string', 'max:255', $contactNoUnique],
            'email' => ['nullable', 'email', 'max:255', $emailUnique],
        ];
    }

    public function many(string $root = 'sales_people'): array
    {
        return [
            $root => ['required', 'array', 'min:1'],
            "{$root}.*.branch_client_key" => ['required', 'string', 'max:100'],
            "{$root}.*.firstname" => ['required', 'string', 'max:255'],
            "{$root}.*.lastname" => ['required', 'string', 'max:255'],
            "{$root}.*.contact_no" => [
                'required',
                'string',
                'max:255',
                Rule::unique('dealer_sale_people', 'contact_no')->whereNull('deleted_at'),
            ],
            "{$root}.*.email" => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('dealer_sale_people', 'email')->whereNull('deleted_at'),
            ],
        ];
    }
}
