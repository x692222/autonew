<?php

namespace App\Http\Requests\Backoffice\System;

use App\Enums\SystemRequestStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemRequestManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('backoffice')?->hasPermissionTo('processSystemRequests', 'backoffice') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(SystemRequestStatusEnum::values())],
            'send_email' => ['nullable', 'boolean'],
            'response' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
