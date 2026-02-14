<?php

namespace App\Http\Requests\Backoffice\System\AccessManagement;

use App\Models\System\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class EditAccessManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $target */
        $target = $this->route('user');

        return Gate::inspect('assignPermissions', $target)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
