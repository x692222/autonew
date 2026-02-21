<?php

namespace App\Http\Requests\Backoffice\Shared\QuotationLookups;

use Illuminate\Foundation\Http\FormRequest;

class LineItemSuggestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:3', 'max:100'],
            'section' => ['required', 'string'],
        ];
    }
}
