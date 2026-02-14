<?php

namespace App\Http\Requests\Backoffice\DealerManagement\Dealers\Users;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateDealerUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        /** @var DealerUser $dealerUser */
        $dealerUser = $this->route('dealerUser');

        return Gate::inspect('updateDealerUser', [$dealer, $dealerUser])->allowed();
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
