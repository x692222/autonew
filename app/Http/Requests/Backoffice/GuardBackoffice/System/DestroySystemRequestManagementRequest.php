<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System;
use Illuminate\Foundation\Http\FormRequest;

class DestroySystemRequestManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('backoffice')?->hasPermissionTo('processSystemRequests', 'backoffice') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

