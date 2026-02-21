<?php

namespace App\Support\Validation\Settings;

use App\Support\Settings\ConfigurationCatalog;
use Illuminate\Validation\Validator;

class DealerSettingsValidation
{
    public function __construct(private readonly ConfigurationCatalog $catalog)
    {
    }

    public function rules(bool $includeBackofficeOnly): array
    {
        return $this->catalog->dealerValidationRules(includeBackofficeOnly: $includeBackofficeOnly);
    }

    public function validatePayload(array $settings, Validator $validator, bool $includeBackofficeOnly): void
    {
        $allowed = $this->catalog->definitionKeys(
            collect($this->catalog->dealerDefinitions())
                ->filter(fn (array $definition) => $includeBackofficeOnly || ($definition['backoffice_only'] ?? false) === false)
                ->all()
        );

        $provided = array_keys($settings);
        $unknown = array_diff($provided, $allowed);

        if ($unknown !== []) {
            $validator->errors()->add('settings', 'One or more dealer setting keys are invalid.');
        }

        $isVatRegistered = filter_var($settings['dealer_is_vat_registered'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $isVatRegistered) {
            return;
        }

        if (($settings['dealer_vat_percentage'] ?? null) === null || $settings['dealer_vat_percentage'] === '') {
            $validator->errors()->add('settings.dealer_vat_percentage', 'Dealer VAT percentage is required when VAT is enabled.');
        }

        if (($settings['dealer_vat_number'] ?? null) === null || trim((string) $settings['dealer_vat_number']) === '') {
            $validator->errors()->add('settings.dealer_vat_number', 'Dealer VAT number is required when VAT is enabled.');
        }
    }
}
