<?php

namespace App\Http\Requests\Backoffice\DealerConfiguration\Settings;

use App\Support\Settings\ConfigurationCatalog;
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
        return app(ConfigurationCatalog::class)->dealerValidationRules(includeBackofficeOnly: false);
    }

    public function withValidator(Validator $validator): void
    {
        $allowed = app(ConfigurationCatalog::class)->definitionKeys(
            collect(app(ConfigurationCatalog::class)->dealerDefinitions())
                ->filter(fn (array $definition) => ($definition['backoffice_only'] ?? false) === false)
                ->all()
        );

        $validator->after(function (Validator $validator) use ($allowed) {
            $provided = array_keys((array) $this->input('settings', []));
            $unknown = array_diff($provided, $allowed);

            if ($unknown !== []) {
                $validator->errors()->add('settings', 'One or more dealer setting keys are invalid.');
            }
        });
    }
}
