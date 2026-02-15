<?php

namespace App\Http\Requests\Backoffice\DealerManagement\Dealers\Leads;

use App\Models\Dealer\Dealer;
use App\Models\Leads\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class EditDealerLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        /** @var Lead $lead */
        $lead = $this->route('lead');

        return Gate::inspect('editLead', [$dealer, $lead])->allowed();
    }

    public function rules(): array
    {
        return [
            'return_to' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
