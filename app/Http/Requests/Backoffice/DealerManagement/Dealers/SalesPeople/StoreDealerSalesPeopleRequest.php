<?php

namespace App\Http\Requests\Backoffice\DealerManagement\Dealers\SalesPeople;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreDealerSalesPeopleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return Gate::inspect('createSalesPerson', $dealer)->allowed();
    }

    public function rules(): array
    {
        return [
            'return_to' => ['nullable', 'string'],
            'branch_id' => ['required', 'string', Rule::exists(DealerBranch::class, 'id')],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'contact_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dealer_sale_people', 'contact_no')->whereNull('deleted_at'),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('dealer_sale_people', 'email')->whereNull('deleted_at'),
            ],
        ];
    }
}
