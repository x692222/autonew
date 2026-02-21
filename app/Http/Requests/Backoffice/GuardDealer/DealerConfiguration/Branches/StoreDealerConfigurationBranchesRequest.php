<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Branches;
use App\Support\Validation\Dealers\DealerBranchValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreDealerConfigurationBranchesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('dealer');
    }

    public function rules(): array
    {
        return array_merge([
            'return_to' => ['nullable', 'string'],
        ], app(DealerBranchValidationRules::class)->single(requireContactNumbers: false));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'contact_numbers' => app(DealerBranchValidationRules::class)->normalizeContactNumbers($this->input('contact_numbers')),
        ]);
    }
}
