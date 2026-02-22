<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\BlockedIps;

use App\Models\Security\BlockedIp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UnblockBlockedIpRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BlockedIp $blockedIp */
        $blockedIp = $this->route('blockedIp');

        return Gate::inspect('delete', $blockedIp)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
