<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Users;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use App\Support\Validation\Dealers\DealerUserValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

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

        return array_merge([
            'return_to' => ['nullable', 'string'],
        ], app(DealerUserValidationRules::class)->single($dealerUser));
    }
}
