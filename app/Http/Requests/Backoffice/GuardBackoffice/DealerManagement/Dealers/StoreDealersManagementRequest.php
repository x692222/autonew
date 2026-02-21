<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Models\WhatsappNumber;
use App\Models\Dealer\Dealer;
use App\Support\Validation\BankingDetails\BankingDetailValidationRules;
use App\Support\Validation\Dealers\DealerBranchValidationRules;
use App\Support\Validation\Dealers\DealerSalesPersonValidationRules;
use App\Support\Validation\Dealers\DealerUserValidationRules;
use App\Support\Validation\Settings\DealerSettingsValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDealersManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('create', Dealer::class)->allowed();
    }

    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_number_id' => [
                'nullable',
                'string',
                Rule::exists('whatsapp_numbers', 'id')
                    ->where(fn ($query) => $query
                        ->where('type', WhatsappNumber::TYPE_DEALER)
                        ->whereNull('dealer_id')
                        ->whereNull('deleted_at')),
            ],

        ], app(DealerBranchValidationRules::class)->many('branches', requireContactNumbers: true), app(DealerUserValidationRules::class)->many('dealer_users'), app(DealerSalesPersonValidationRules::class)->many('sales_people'), app(BankingDetailValidationRules::class)->upsertMany('banking_details', required: true), app(DealerSettingsValidation::class)->rules(includeBackofficeOnly: true));
    }

    public function withValidator(Validator $validator): void
    {
        $settingsValidation = app(DealerSettingsValidation::class);

        $validator->after(function ($validator) {
            $branchKeys = collect($this->input('branches', []))
                ->pluck('client_key')
                ->filter(fn ($value) => filled($value))
                ->map(fn ($value) => (string) $value)
                ->values()
                ->all();

            foreach (($this->input('sales_people', [])) as $index => $salesPerson) {
                $key = (string) ($salesPerson['branch_client_key'] ?? '');

                if ($key === '' || in_array($key, $branchKeys, true)) {
                    continue;
                }

                $validator->errors()->add("sales_people.{$index}.branch_client_key", 'Selected branch is invalid.');
            }
        });

        $validator->after(function (Validator $validator) use ($settingsValidation) {
            $settingsValidation->validatePayload(
                settings: (array) $this->input('settings', []),
                validator: $validator,
                includeBackofficeOnly: true
            );
        });
    }

    protected function prepareForValidation(): void
    {
        $branchValidator = app(DealerBranchValidationRules::class);

        $normalizedBranches = collect((array) $this->input('branches', []))
            ->map(function ($branch) use ($branchValidator) {
                if (!is_array($branch)) {
                    return $branch;
                }

                $branch['contact_numbers'] = $branchValidator->normalizeContactNumbers($branch['contact_numbers'] ?? null);

                return $branch;
            })
            ->all();

        $this->merge([
            'branches' => $normalizedBranches,
        ]);
    }
}
