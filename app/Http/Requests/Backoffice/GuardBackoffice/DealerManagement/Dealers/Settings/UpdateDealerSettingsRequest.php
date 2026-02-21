<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Settings;
use App\Models\Dealer\Dealer;
use App\Support\Validation\Settings\DealerSettingsValidation;
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
        return app(DealerSettingsValidation::class)->rules(includeBackofficeOnly: true);
    }

    public function withValidator(Validator $validator): void
    {
        $validation = app(DealerSettingsValidation::class);

        $validator->after(function (Validator $validator) use ($validation) {
            $validation->validatePayload(
                settings: (array) $this->input('settings', []),
                validator: $validator,
                includeBackofficeOnly: true
            );
        });
    }
}
