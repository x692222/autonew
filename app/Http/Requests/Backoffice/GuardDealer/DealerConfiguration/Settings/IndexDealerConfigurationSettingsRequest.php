<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Settings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexDealerConfigurationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        $dealer = $actor?->dealer;

        return (bool) $dealer && Gate::forUser($actor)->inspect('dealerConfigurationConfigureSettings', $dealer)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
