<?php

namespace App\Http\Requests\Backoffice\System\UsersManagement;

use App\Models\System\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateUsersManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $target */
        $target = $this->route('user');

        return Gate::inspect('update', $target)->allowed();
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'firstname' => ['required', 'string', 'max:75'],
            'lastname' => ['required', 'string', 'max:75'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->getKey())],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'backoffice')],
        ];
    }
}
