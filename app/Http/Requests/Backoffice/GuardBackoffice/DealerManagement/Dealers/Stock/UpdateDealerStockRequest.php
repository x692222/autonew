<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock;
use App\Models\Dealer\Dealer;
use App\Models\Stock\Stock;
use App\Support\Stock\StockValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateDealerStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        /** @var Stock $stock */
        $stock = $this->route('stock');
        return Gate::inspect('editStock', [$dealer, $stock])->allowed();
    }

    public function rules(): array
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        /** @var Stock $stock */
        $stock = $this->route('stock');

        $rules = [
            ...StockValidationRules::baseUpdate($stock),
            ...StockValidationRules::typedRules((string) $stock->type),
        ];

        $rules['branch_id'] = [
            'required',
            'string',
            Rule::exists('dealer_branches', 'id')->where('dealer_id', (string) $dealer->id),
        ];

        return $rules;
    }
}
