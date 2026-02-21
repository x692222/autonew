<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\LocationsManagement;
use App\Support\Resolvers\Locations\LocationTypeResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CreateLocationsManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = (string) $this->route('type');
        $class = LocationTypeResolver::modelClass($type);

        return Gate::inspect('create', $class)->allowed();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(LocationTypeResolver::types())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['type' => $this->route('type')]);
    }
}
