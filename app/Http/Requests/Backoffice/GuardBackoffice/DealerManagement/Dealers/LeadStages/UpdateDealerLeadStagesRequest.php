<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\LeadStages;
use App\Models\Dealer\Dealer;
use App\Models\Leads\LeadPipeline;
use App\Models\Leads\LeadStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateDealerLeadStagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        /** @var LeadStage $stage */
        $stage = $this->route('leadStage');

        return Gate::inspect('editLeadStage', [$dealer, $stage])->allowed();
    }

    public function rules(): array
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return [
            'return_to' => ['nullable', 'string', 'max:2000'],
            'pipeline_id' => ['required', 'string', Rule::exists(LeadPipeline::class, 'id')->where('dealer_id', (string) $dealer->id)],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_terminal' => ['nullable', 'boolean'],
            'is_won' => ['nullable', 'boolean'],
            'is_lost' => ['nullable', 'boolean'],
        ];
    }
}
