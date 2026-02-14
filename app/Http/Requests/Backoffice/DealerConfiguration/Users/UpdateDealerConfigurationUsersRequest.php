<?php

namespace App\Http\Requests\Backoffice\DealerConfiguration\Users;

use App\Models\Dealer\DealerUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        return [
            'return_to' => ['nullable', 'string'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('dealer_users', 'email')->ignore($dealerUser->id)],
        ];
    }
}
