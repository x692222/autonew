<?php

namespace App\Http\Requests\Backoffice\System\Settings;

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
        });
    }
}
