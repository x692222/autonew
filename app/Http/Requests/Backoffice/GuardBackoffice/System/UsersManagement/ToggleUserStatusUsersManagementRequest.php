<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\UsersManagement;
use App\Models\System\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ToggleUserStatusUsersManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $target */
        $target = $this->route('user');

        return Gate::inspect('update', $target)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
