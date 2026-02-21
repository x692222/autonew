<?php

namespace App\Http\Requests\Backoffice\Shared\Notes;

use Illuminate\Foundation\Http\FormRequest;

class UpsertNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string'],
            'backoffice_only' => ['nullable', 'boolean'],
        ];
    }
}
