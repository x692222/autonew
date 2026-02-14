<?php

namespace App\Http\Requests\Backoffice\DealerManagement\Dealers\SalesPeople;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexDealerSalesPeopleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return Gate::inspect('showSalesPeople', $dealer)->allowed();
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
