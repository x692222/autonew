<?php

namespace App\Http\Requests\Backoffice\DealerManagement\Dealers\SalesPeople;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerSalePerson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DestroyDealerSalesPeopleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        /** @var DealerSalePerson $salesPerson */
        $salesPerson = $this->route('salesPerson');

        return Gate::inspect('deleteSalesPerson', [$dealer, $salesPerson])->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
