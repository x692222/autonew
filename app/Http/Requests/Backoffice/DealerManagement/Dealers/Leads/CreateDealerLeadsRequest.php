<?php

namespace App\Http\Requests\Backoffice\DealerManagement\Dealers\Leads;

use App\Models\Dealer\Dealer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CreateDealerLeadsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return Gate::inspect('createLead', $dealer)->allowed();
    }

    public function rules(): array
    {
        return [
            'return_to' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
