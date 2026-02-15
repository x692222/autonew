<?php

namespace App\Http\Requests\Backoffice\DealerManagement\Dealers\Users;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateDealerUserPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        /** @var DealerUser $dealerUser */
        $dealerUser = $this->route('dealerUser');

        return Gate::inspect('assignDealerUserPermissions', [$dealer, $dealerUser])->allowed();
    }

    public function rules(): array
    {
        return [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'dealer'),
            ],
        ];
    }
}
