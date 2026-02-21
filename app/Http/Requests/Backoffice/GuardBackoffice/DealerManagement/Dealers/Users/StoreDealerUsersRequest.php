<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Users;
use App\Models\Dealer\Dealer;
use App\Support\Validation\Dealers\DealerUserValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDealerUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return Gate::inspect('createDealerUser', $dealer)->allowed();
    }

    public function rules(): array
    {
        return array_merge([
            'return_to' => ['nullable', 'string'],
        ], app(DealerUserValidationRules::class)->single());
    }
}
