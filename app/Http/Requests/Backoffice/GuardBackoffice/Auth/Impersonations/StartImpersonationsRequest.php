<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\Auth\Impersonations;
use App\Models\Dealer\DealerUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class StartImpersonationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!Auth::guard('backoffice')->check()) {
            return false;
        }

        if (Auth::guard('dealer')->check()) {
            return false;
        }

        $target = DealerUser::query()->where('email', (string) $this->input('email'))->first();

        if (!$target) {
            return Auth::guard('backoffice')->user()?->hasPermissionTo('impersonateDealershipUser', 'backoffice') ?? false;
        }

        return Gate::inspect('impersonate', $target)->allowed();
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'exists:dealer_users,email'],
        ];
    }
}
