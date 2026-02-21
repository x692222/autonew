<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Users;
use App\Models\Dealer\Dealer;
use App\Support\Validation\Dealers\DealerUserValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDealerConfigurationUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer|null $dealer */
        $dealer = $this->user('dealer')?->dealer;

        return (bool) $dealer && Gate::forUser($this->user('dealer'))->inspect('dealerConfigurationCreateUser', $dealer)->allowed();
    }

    public function rules(): array
    {
        return array_merge([
            'return_to' => ['nullable', 'string'],
        ], app(DealerUserValidationRules::class)->single());
    }
}
