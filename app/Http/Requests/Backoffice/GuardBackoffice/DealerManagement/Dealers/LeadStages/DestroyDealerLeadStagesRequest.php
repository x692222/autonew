<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\LeadStages;
use App\Models\Dealer\Dealer;
use App\Models\Leads\LeadStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DestroyDealerLeadStagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        /** @var LeadStage $stage */
        $stage = $this->route('leadStage');

        return Gate::inspect('deleteLeadStage', [$dealer, $stage])->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
