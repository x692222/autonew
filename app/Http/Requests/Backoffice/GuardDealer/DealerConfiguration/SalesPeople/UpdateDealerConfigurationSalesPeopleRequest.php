<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\SalesPeople;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerSalePerson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDealerConfigurationSalesPeopleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('dealer');
    }

    public function rules(): array
    {
        /** @var DealerSalePerson $salesPerson */
        $salesPerson = $this->route('salesPerson');

        return [
            'return_to' => ['nullable', 'string'],
            'branch_id' => ['required', 'string', Rule::exists(DealerBranch::class, 'id')],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'contact_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dealer_sale_people', 'contact_no')->ignore($salesPerson->id)->whereNull('deleted_at'),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('dealer_sale_people', 'email')->ignore($salesPerson->id)->whereNull('deleted_at'),
            ],
        ];
    }
}
