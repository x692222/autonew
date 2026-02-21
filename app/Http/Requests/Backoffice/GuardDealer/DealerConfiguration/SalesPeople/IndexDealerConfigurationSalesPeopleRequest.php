<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\SalesPeople;
use App\Models\Dealer\DealerBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexDealerConfigurationSalesPeopleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('dealer');
    }

    public function rules(): array
    {
        $dealer = $this->user('dealer')?->dealer;

        return [
            'branch_id' => ['nullable', 'string', Rule::exists(DealerBranch::class, 'id')->where('dealer_id', (string) $dealer?->id)],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'string', 'max:50'],
            'descending' => ['nullable'],
        ];
    }
}
