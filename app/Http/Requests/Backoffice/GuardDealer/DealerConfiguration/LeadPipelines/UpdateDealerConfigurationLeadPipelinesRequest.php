<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\LeadPipelines;
use App\Models\Leads\LeadPipeline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateDealerConfigurationLeadPipelinesRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
