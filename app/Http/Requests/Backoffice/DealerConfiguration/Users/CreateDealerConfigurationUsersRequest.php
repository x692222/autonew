<?php

namespace App\Http\Requests\Backoffice\DealerConfiguration\Users;

use App\Models\Dealer\Dealer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CreateDealerConfigurationUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer|null $dealer */
        $dealer = $this->user('dealer')?->dealer;

        return (bool) $dealer && Gate::forUser($this->user('dealer'))->inspect('dealerConfigurationCreateUser', $dealer)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
