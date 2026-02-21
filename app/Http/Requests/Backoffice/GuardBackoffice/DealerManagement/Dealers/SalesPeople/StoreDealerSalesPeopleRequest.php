<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\SalesPeople;
use App\Models\Dealer\Dealer;
use App\Support\Validation\Dealers\DealerSalesPersonValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

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
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return array_merge([
            'return_to' => ['nullable', 'string'],
        ], app(DealerSalesPersonValidationRules::class)->singleForDealer($dealer));
    }
}
