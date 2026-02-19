<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Stock;
use App\Models\Stock\Stock;
use App\Support\Stock\StockValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreDealerConfigurationStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        $dealer = $actor?->dealer;

        return (bool) $dealer && Gate::forUser($actor)->inspect('dealerConfigurationCreateStock', $dealer)->allowed();
    }

    public function rules(): array
    {
        $dealer = $this->user('dealer')?->dealer;
        $type = (string) $this->input('type', '');

        $rules = [
            ...StockValidationRules::baseCreate(),
            ...StockValidationRules::typedRules($type),
            'type' => ['required', 'string', 'in:' . implode(',', Stock::STOCK_TYPE_OPTIONS)],
        ];

        $rules['branch_id'] = [
            'required',
            'string',
            Rule::exists('dealer_branches', 'id')->where('dealer_id', (string) $dealer?->id),
        ];

        return $rules;
    }
}
