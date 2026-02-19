<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\LocationsManagement;
use App\Models\Location\LocationCountry;
use App\Support\Locations\LocationTypeResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexLocationsManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('viewAny', LocationCountry::class)->allowed();
    }

    public function rules(): array
    {
        return [
            'tab' => ['nullable', Rule::in(LocationTypeResolver::types())],
            'search' => ['nullable', 'string', 'max:100'],
            'country_id' => ['nullable', 'string', 'exists:location_countries,id'],
            'state_id' => ['nullable', 'string', 'exists:location_states,id'],
            'city_id' => ['nullable', 'string', 'exists:location_cities,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'string', 'max:50'],
            'descending' => ['nullable'],
        ];
    }
}
