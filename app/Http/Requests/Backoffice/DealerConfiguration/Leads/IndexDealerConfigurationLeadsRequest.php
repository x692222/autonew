<?php

namespace App\Http\Requests\Backoffice\DealerConfiguration\Leads;

use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerUser;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadPipeline;
use App\Models\Leads\LeadStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexDealerConfigurationLeadsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        $dealer = $actor?->dealer;

        return (bool) $dealer && Gate::forUser($actor)->inspect('dealerConfigurationManageLeads', $dealer)->allowed();
    }

    public function rules(): array
    {
        $actor = $this->user('dealer');
        $dealer = $actor?->dealer;

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['nullable', 'string', Rule::exists(DealerBranch::class, 'id')->where('dealer_id', (string) $dealer?->id)],
            'assigned_to_dealer_user_id' => ['nullable', 'string', Rule::exists(DealerUser::class, 'id')->where('dealer_id', (string) $dealer?->id)],
            'pipeline_id' => ['nullable', 'string', Rule::exists(LeadPipeline::class, 'id')->where('dealer_id', (string) $dealer?->id)],
            'stage_id' => ['nullable', 'string', Rule::exists(LeadStage::class, 'id')->whereIn('pipeline_id', LeadPipeline::query()->select('id')->where('dealer_id', (string) $dealer?->id))],
            'lead_status' => ['nullable', Rule::in(Lead::LEAD_STATUSES)],
            'source' => ['nullable', Rule::in(Lead::LEAD_SOURCES)],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'string', 'max:50'],
            'descending' => ['nullable'],
        ];
    }
}
