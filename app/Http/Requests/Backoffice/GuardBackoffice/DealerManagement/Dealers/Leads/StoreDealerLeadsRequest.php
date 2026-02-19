<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Leads;
use App\Enums\LeadCorrespondenceLanguageEnum;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerUser;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadPipeline;
use App\Models\Leads\LeadStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreDealerLeadsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return Gate::inspect('createLead', $dealer)->allowed();
    }

    public function rules(): array
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return [
            'return_to' => ['nullable', 'string', 'max:2000'],
            'branch_id' => ['nullable', 'string', Rule::exists(DealerBranch::class, 'id')->where('dealer_id', (string) $dealer->id)],
            'assigned_to_dealer_user_id' => ['nullable', 'string', Rule::exists(DealerUser::class, 'id')->where('dealer_id', (string) $dealer->id)],
            'pipeline_id' => ['nullable', 'string', Rule::exists(LeadPipeline::class, 'id')->where('dealer_id', (string) $dealer->id)],
            'stage_id' => ['nullable', 'string', Rule::exists(LeadStage::class, 'id')->whereIn('pipeline_id', LeadPipeline::query()->select('id')->where('dealer_id', (string) $dealer->id))],
            'firstname' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_no' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', Rule::in(Lead::LEAD_SOURCES)],
            'status' => ['nullable', Rule::in(Lead::LEAD_STATUSES)],
            'correspondence_language' => ['nullable', Rule::in(LeadCorrespondenceLanguageEnum::values())],
            'registration_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
