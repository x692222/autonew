<?php

namespace App\Http\Requests\Backoffice\DealerManagement\Dealers\Users;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class EditDealerUsersRequest extends FormRequest
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
        return [];
    }
}
