<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Settings;
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

            $settings = (array) $this->input('settings', []);
            $isVatRegistered = filter_var($settings['dealer_is_vat_registered'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($isVatRegistered) {
                if (($settings['dealer_vat_percentage'] ?? null) === null || $settings['dealer_vat_percentage'] === '') {
                    $validator->errors()->add('settings.dealer_vat_percentage', 'Dealer VAT percentage is required when VAT is enabled.');
                }

                if (($settings['dealer_vat_number'] ?? null) === null || trim((string) $settings['dealer_vat_number']) === '') {
                    $validator->errors()->add('settings.dealer_vat_number', 'Dealer VAT number is required when VAT is enabled.');
                }
            }
        });
    }
}
