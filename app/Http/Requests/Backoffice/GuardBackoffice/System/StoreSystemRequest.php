<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System;
use App\Models\System\SystemRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreSystemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user('backoffice') || $this->user('dealer'));
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:' . implode(',', SystemRequest::REQUEST_TYPES)],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
