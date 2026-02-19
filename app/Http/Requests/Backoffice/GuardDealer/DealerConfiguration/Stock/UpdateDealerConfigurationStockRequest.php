<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Stock;
use App\Models\Stock\Stock;
use App\Support\Stock\StockValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateDealerConfigurationStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        /** @var Stock $stock */
        $stock = $this->route('stock');

        return (bool) $actor && Gate::forUser($actor)->inspect('dealerConfigurationEditStock', $stock)->allowed();
    }

    public function rules(): array
    {
        $dealer = $this->user('dealer')?->dealer;
        /** @var Stock $stock */
        $stock = $this->route('stock');

        $rules = [
            ...StockValidationRules::baseUpdate($stock),
            ...StockValidationRules::typedRules((string) $stock->type),
        ];

        $rules['branch_id'] = [
            'required',
            'string',
            Rule::exists('dealer_branches', 'id')->where('dealer_id', (string) $dealer?->id),
        ];

        return $rules;
    }
}
