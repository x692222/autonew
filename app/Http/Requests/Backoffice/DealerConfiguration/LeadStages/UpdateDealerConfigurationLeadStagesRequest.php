<?php

namespace App\Http\Requests\Backoffice\DealerConfiguration\LeadStages;

use App\Models\Leads\LeadPipeline;
use App\Models\Leads\LeadStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateDealerConfigurationLeadStagesRequest extends FormRequest
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
        $actor = $this->user('dealer');

        return [
            'return_to' => ['nullable', 'string', 'max:2000'],
            'pipeline_id' => ['required', 'string', Rule::exists(LeadPipeline::class, 'id')->where('dealer_id', (string) $actor?->dealer_id)],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_terminal' => ['nullable', 'boolean'],
            'is_won' => ['nullable', 'boolean'],
            'is_lost' => ['nullable', 'boolean'],
        ];
    }
}
