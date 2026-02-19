<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Settings;
use App\Models\Dealer\Dealer;
use App\Support\Settings\ConfigurationCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpdateDealerSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');

        return Gate::inspect('showSettings', $dealer)->allowed();
    }

    public function rules(): array
    {
        return app(ConfigurationCatalog::class)->dealerValidationRules(includeBackofficeOnly: true);
    }

    public function withValidator(Validator $validator): void
    {
        $allowed = app(ConfigurationCatalog::class)->definitionKeys(
            app(ConfigurationCatalog::class)->dealerDefinitions()
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
