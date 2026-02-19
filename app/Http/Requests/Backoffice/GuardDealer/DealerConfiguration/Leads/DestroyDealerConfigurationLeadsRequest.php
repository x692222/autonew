<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Leads;
use App\Models\Leads\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DestroyDealerConfigurationLeadsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        /** @var Lead $lead */
        $lead = $this->route('lead');

        return (bool) $actor && Gate::forUser($actor)->inspect('dealerConfigurationDeleteLead', $lead)->allowed();
    }

    public function rules(): array
    {
        return [
            'return_to' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
