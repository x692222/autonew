<?php

namespace App\Http\Requests\Backoffice\DealerConfiguration\LeadPipelines;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDealerConfigurationLeadPipelinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        $dealer = $actor?->dealer;

        return (bool) $dealer && Gate::forUser($actor)->inspect('dealerConfigurationCreatePipeline', $dealer)->allowed();
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
