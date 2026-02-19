<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\LeadStages;
use App\Models\Leads\LeadStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class EditDealerConfigurationLeadStagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        /** @var LeadStage $stage */
        $stage = $this->route('leadStage');

        return (bool) $actor && Gate::forUser($actor)->inspect('dealerConfigurationEditPipelineStage', $stage)->allowed();
    }

    public function rules(): array
    {
        return [
            'return_to' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
