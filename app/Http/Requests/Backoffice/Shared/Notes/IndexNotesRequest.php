<?php

namespace App\Http\Requests\Backoffice\Shared\Notes;

use Illuminate\Foundation\Http\FormRequest;

class IndexNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'author_guard' => ['nullable', 'in:backoffice,dealer'],
            'author_id' => ['nullable', 'uuid'],
            'backoffice_only' => ['nullable', 'in:0,1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sortBy' => ['nullable', 'in:created_at,updated_at'],
            'descending' => ['nullable'],
        ];
    }
}
