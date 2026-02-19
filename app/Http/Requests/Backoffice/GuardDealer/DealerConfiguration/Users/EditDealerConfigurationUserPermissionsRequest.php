<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Users;
use App\Models\Dealer\DealerUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class EditDealerConfigurationUserPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var DealerUser $dealerUser */
        $dealerUser = $this->route('dealerUser');
        $actor = $this->user('dealer');

        return Gate::forUser($actor)->inspect('dealerConfigurationAssignUserPermissions', $dealerUser)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
