<?php

namespace App\Http\Requests\Backoffice\System\LocationsManagement;

use App\Support\Locations\LocationTypeResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DestroyLocationsManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = (string) $this->route('type');
        $location = LocationTypeResolver::findOrFail($type, (string) $this->route('location'));

        return Gate::inspect('delete', $location)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
