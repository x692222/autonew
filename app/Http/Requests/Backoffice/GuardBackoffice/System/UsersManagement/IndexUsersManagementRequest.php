<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\UsersManagement;
use App\Models\System\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexUsersManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('viewAny', User::class)->allowed();
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'string', 'max:50'],
            'descending' => ['nullable'],
        ];
    }
}
