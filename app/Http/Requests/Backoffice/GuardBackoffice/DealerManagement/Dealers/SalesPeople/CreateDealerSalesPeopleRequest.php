<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\SalesPeople;
use App\Models\Dealer\Dealer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CreateDealerSalesPeopleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return Gate::inspect('createSalesPerson', $dealer)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
