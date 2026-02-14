<?php

namespace App\Http\Requests\Backoffice\System\UsersManagement;

use App\Models\System\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CreateUsersManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('create', User::class)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
