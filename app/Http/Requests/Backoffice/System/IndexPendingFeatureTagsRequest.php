<?php

namespace App\Http\Requests\Backoffice\System;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPendingFeatureTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('backoffice')?->hasPermissionTo('processSystemRequests', 'backoffice') ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', Rule::in(['pending', 'reviewed'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'string', 'max:50'],
            'descending' => ['nullable'],
        ];
    }
}

