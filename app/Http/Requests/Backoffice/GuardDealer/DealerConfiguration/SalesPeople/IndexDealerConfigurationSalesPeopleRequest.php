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
        return [
            'branch_id' => ['nullable', 'string', Rule::exists(DealerBranch::class, 'id')],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'string', 'max:50'],
            'descending' => ['nullable'],
        ];
    }
}
