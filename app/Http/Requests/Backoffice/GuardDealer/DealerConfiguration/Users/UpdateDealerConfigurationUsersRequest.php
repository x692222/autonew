<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Users;
use App\Models\Dealer\DealerUser;
use App\Support\Validation\Dealers\DealerUserValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDealerConfigurationUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('dealer');
    }

    public function rules(): array
    {
        /** @var DealerUser $dealerUser */
        $dealerUser = $this->route('dealerUser');

        return array_merge([
            'return_to' => ['nullable', 'string'],
        ], app(DealerUserValidationRules::class)->single($dealerUser));
    }
}
