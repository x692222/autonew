<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\LocationsManagement;
use App\Support\Resolvers\Locations\LocationTypeResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EditLocationsManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = (string) $this->route('type');
        $location = LocationTypeResolver::findOrFail($type, (string) $this->route('location'));

        return Gate::inspect('update', $location)->allowed();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(LocationTypeResolver::types())],
            'location' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->route('type'),
            'location' => $this->route('location'),
        ]);
    }
}
