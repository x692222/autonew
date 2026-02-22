<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\BlockedIps;

use App\Models\Security\BlockedIp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexBlockedIpsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('viewAny', BlockedIp::class)->allowed();
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'guard_name' => ['nullable', Rule::in(['backoffice', 'dealer'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', Rule::in(['blocked_at', 'ip_address', 'guard_name', 'failed_attempts'])],
            'descending' => ['nullable'],
        ];
    }
}
