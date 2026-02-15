<?php

namespace App\Http\Requests\Backoffice\DealerConfiguration\LeadPipelines;

use App\Models\Leads\LeadPipeline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class EditDealerConfigurationLeadPipelinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        /** @var LeadPipeline $pipeline */
        $pipeline = $this->route('leadPipeline');

        return (bool) $actor && Gate::forUser($actor)->inspect('dealerConfigurationEditPipeline', $pipeline)->allowed();
    }

    public function rules(): array
    {
        return [
            'return_to' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
