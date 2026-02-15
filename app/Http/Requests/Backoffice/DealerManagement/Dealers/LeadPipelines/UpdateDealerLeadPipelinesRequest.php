<?php

namespace App\Http\Requests\Backoffice\DealerManagement\Dealers\LeadPipelines;

use App\Models\Dealer\Dealer;
use App\Models\Leads\LeadPipeline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateDealerLeadPipelinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        /** @var LeadPipeline $pipeline */
        $pipeline = $this->route('leadPipeline');

        return Gate::inspect('editLeadPipeline', [$dealer, $pipeline])->allowed();
    }

    public function rules(): array
    {
        return [
            'return_to' => ['nullable', 'string', 'max:2000'],
            'name' => ['required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
