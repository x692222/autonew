<?php

namespace App\Http\Requests\Backoffice\System\LocationsManagement;

use App\Support\Locations\LocationTypeResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreLocationsManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = (string) $this->route('type');
        $class = LocationTypeResolver::modelClass($type);

        return Gate::inspect('create', $class)->allowed();
    }

    public function rules(): array
    {
        $type = (string) $this->route('type');

        return [
            'name' => ['required', 'string', 'max:120'],
            'country_id' => [
                Rule::requiredIf($type === LocationTypeResolver::STATE),
                'nullable',
                'string',
                'exists:location_countries,id',
            ],
            'state_id' => [
                Rule::requiredIf($type === LocationTypeResolver::CITY),
                'nullable',
                'string',
                'exists:location_states,id',
            ],
            'city_id' => [
                Rule::requiredIf($type === LocationTypeResolver::SUBURB),
                'nullable',
                'string',
                'exists:location_cities,id',
            ],
        ];
    }
}
