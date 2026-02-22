<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\UsersManagement;
use App\Models\System\User;
use App\Support\Validation\Users\SystemUserValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreUsersManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('create', User::class)->allowed();
    }

    public function rules(): array
    {
        return app(SystemUserValidationRules::class)->store();
    }
}
