<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\SalesPeople;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerSalePerson;
use App\Support\Validation\Dealers\DealerSalesPersonValidationRules;
use Illuminate\Foundation\Http\FormRequest;

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
        /** @var Dealer $dealer */
        $dealer = $this->user('dealer')->dealer;

        return array_merge([
            'return_to' => ['nullable', 'string'],
        ], app(DealerSalesPersonValidationRules::class)->singleForDealer($dealer, $salesPerson));
    }
}
