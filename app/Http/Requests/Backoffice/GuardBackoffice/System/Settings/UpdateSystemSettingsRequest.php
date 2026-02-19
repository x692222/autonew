<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\Settings;
use App\Models\System\Configuration\SystemConfiguration;
use App\Support\Settings\ConfigurationCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('update', SystemConfiguration::class)->allowed();
    }

    public function rules(): array
    {
        return app(ConfigurationCatalog::class)->systemValidationRules();
    }

    public function withValidator(Validator $validator): void
    {
        $allowed = app(ConfigurationCatalog::class)->definitionKeys(
            app(ConfigurationCatalog::class)->systemDefinitions()
        );

        $validator->after(function (Validator $validator) use ($allowed) {
            $provided = array_keys((array) $this->input('settings', []));
            $unknown = array_diff($provided, $allowed);

            if ($unknown !== []) {
                $validator->errors()->add('settings', 'One or more system setting keys are invalid.');
            }

            $settings = (array) $this->input('settings', []);
            $isVatRegistered = filter_var($settings['system_is_vat_registered'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($isVatRegistered) {
                if (($settings['system_vat_percentage'] ?? null) === null || $settings['system_vat_percentage'] === '') {
                    $validator->errors()->add('settings.system_vat_percentage', 'System VAT percentage is required when VAT is enabled.');
                }

                if (($settings['system_vat_number'] ?? null) === null || trim((string) $settings['system_vat_number']) === '') {
                    $validator->errors()->add('settings.system_vat_number', 'System VAT number is required when VAT is enabled.');
                }
            }
        });
    }
}
