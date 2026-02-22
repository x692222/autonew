<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\UsersManagement;
use App\Models\System\User;
use App\Support\Validation\Users\SystemUserValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

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

        return app(SystemUserValidationRules::class)->update($user);
    }
}
