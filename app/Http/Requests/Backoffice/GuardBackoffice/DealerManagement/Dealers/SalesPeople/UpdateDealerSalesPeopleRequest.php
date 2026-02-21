<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\SalesPeople;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerSalePerson;
use App\Support\Validation\Dealers\DealerSalesPersonValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateDealerSalesPeopleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        /** @var DealerSalePerson $salesPerson */
        $salesPerson = $this->route('salesPerson');

        return Gate::inspect('updateSalesPerson', [$dealer, $salesPerson])->allowed();
    }

    public function rules(): array
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        /** @var DealerSalePerson $salesPerson */
        $salesPerson = $this->route('salesPerson');

        return array_merge([
            'return_to' => ['nullable', 'string'],
        ], app(DealerSalesPersonValidationRules::class)->singleForDealer($dealer, $salesPerson));
    }
}
