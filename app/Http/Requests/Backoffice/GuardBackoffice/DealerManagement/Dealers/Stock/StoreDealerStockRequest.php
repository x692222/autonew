<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock;
use App\Models\Dealer\Dealer;
use App\Models\Stock\Stock;
use App\Support\Validation\Stock\StockValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreDealerStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        return Gate::inspect('createStock', $dealer)->allowed();
    }

    public function rules(): array
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        $type = (string) $this->input('type', '');

        $rules = [
            ...StockValidationRules::baseCreate(),
            ...StockValidationRules::typedRules($type),
            'type' => ['required', 'string', 'in:' . implode(',', Stock::STOCK_TYPE_OPTIONS)],
        ];

        $rules['branch_id'] = [
            'required',
            'string',
            Rule::exists('dealer_branches', 'id')->where('dealer_id', (string) $dealer->id),
        ];

        return $rules;
    }
}
