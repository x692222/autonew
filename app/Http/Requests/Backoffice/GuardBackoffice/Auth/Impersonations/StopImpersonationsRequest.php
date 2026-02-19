<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\Auth\Impersonations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StopImpersonationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('backoffice')->check() && (bool) $this->session()->get('impersonation.active');
    }

    public function rules(): array
    {
        return [];
    }
}
