<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Branches;
use App\Models\Location\LocationCity;
use App\Models\Location\LocationCountry;
use App\Models\Location\LocationState;
use App\Models\Location\LocationSuburb;
use App\Models\Stock\Stock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexDealerConfigurationBranchesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('dealer');
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(Stock::STOCK_TYPE_OPTIONS)],
            'country_id' => ['nullable', 'string', Rule::exists(LocationCountry::class, 'id')],
            'state_id' => ['nullable', 'string', Rule::exists(LocationState::class, 'id')],
            'city_id' => ['nullable', 'string', Rule::exists(LocationCity::class, 'id')],
            'suburb_id' => ['nullable', 'string', Rule::exists(LocationSuburb::class, 'id')],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'string', 'max:50'],
            'descending' => ['nullable'],
        ];
    }
}
