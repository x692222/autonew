<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Models\Dealer\Dealer;
use App\Models\Location\LocationCity;
use App\Models\Location\LocationCountry;
use App\Models\Location\LocationState;
use App\Models\Location\LocationSuburb;
use App\Models\Stock\Stock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexDealersManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('viewAny', Dealer::class)->allowed();
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
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
