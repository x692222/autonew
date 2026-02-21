<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Settings;
use App\Support\Validation\Settings\DealerSettingsValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpdateDealerConfigurationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        $dealer = $actor?->dealer;

        return (bool) $dealer && Gate::forUser($actor)->inspect('dealerConfigurationConfigureSettings', $dealer)->allowed();
    }

    public function rules(): array
    {
        return app(DealerSettingsValidation::class)->rules(includeBackofficeOnly: false);
    }

    public function withValidator(Validator $validator): void
    {
        $validation = app(DealerSettingsValidation::class);

        $validator->after(function (Validator $validator) use ($validation) {
            $validation->validatePayload(
                settings: (array) $this->input('settings', []),
                validator: $validator,
                includeBackofficeOnly: false
            );
        });
    }
}
