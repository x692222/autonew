<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\UsersManagement;
use App\Models\System\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreUsersManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('create', User::class)->allowed();
    }

    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:75'],
            'lastname' => ['required', 'string', 'max:75'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'backoffice')],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
