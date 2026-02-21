<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\SalesPeople;
use App\Models\Dealer\Dealer;
use App\Support\Validation\Dealers\DealerSalesPersonValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDealerConfigurationSalesPeopleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer|null $dealer */
        $dealer = $this->user('dealer')?->dealer;

        return (bool) $dealer && Gate::forUser($this->user('dealer'))->inspect('dealerConfigurationCreateSalesPerson', $dealer)->allowed();
    }

    public function rules(): array
    {
        /** @var Dealer $dealer */
        $dealer = $this->user('dealer')->dealer;

        return array_merge([
            'return_to' => ['nullable', 'string'],
        ], app(DealerSalesPersonValidationRules::class)->singleForDealer($dealer));
    }
}
