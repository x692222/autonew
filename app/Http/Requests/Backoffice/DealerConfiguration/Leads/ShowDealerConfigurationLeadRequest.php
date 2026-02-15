<?php

namespace App\Http\Requests\Backoffice\DealerConfiguration\Leads;

use App\Models\Leads\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ShowDealerConfigurationLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        /** @var Lead $lead */
        $lead = $this->route('lead');

        return (bool) $actor && Gate::forUser($actor)->inspect('dealerConfigurationViewLead', $lead)->allowed();
    }

    public function rules(): array
    {
        return [
            'return_to' => ['nullable', 'string', 'max:2000'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'string', 'max:50'],
            'descending' => ['nullable'],
        ];
    }
}
