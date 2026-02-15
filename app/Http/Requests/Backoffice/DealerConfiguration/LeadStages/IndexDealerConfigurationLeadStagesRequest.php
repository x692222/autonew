<?php

namespace App\Http\Requests\Backoffice\DealerConfiguration\LeadStages;

use App\Models\Leads\LeadPipeline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexDealerConfigurationLeadStagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        $dealer = $actor?->dealer;

        return (bool) $dealer && Gate::forUser($actor)->inspect('dealerConfigurationIndexPipelineStages', $dealer)->allowed();
    }

    public function rules(): array
    {
        $actor = $this->user('dealer');

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'pipeline_id' => ['nullable', 'string', Rule::exists(LeadPipeline::class, 'id')->where('dealer_id', (string) $actor?->dealer_id)],
            'is_terminal' => ['nullable', 'boolean'],
            'is_won' => ['nullable', 'boolean'],
            'is_lost' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'string', 'max:50'],
            'descending' => ['nullable'],
        ];
    }
}
