<?php

namespace App\Http\Requests\Backoffice\DealerManagement\Dealers\LeadStages;

use App\Models\Dealer\Dealer;
use App\Models\Leads\LeadPipeline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class IndexDealerLeadStagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return Gate::inspect('showLeadStages', $dealer)->allowed();
    }

    public function rules(): array
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'pipeline_id' => ['nullable', 'string', Rule::exists(LeadPipeline::class, 'id')->where('dealer_id', (string) $dealer->id)],
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
