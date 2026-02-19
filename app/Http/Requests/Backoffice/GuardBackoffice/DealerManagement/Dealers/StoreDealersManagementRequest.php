<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Models\WhatsappNumber;
use App\Models\Dealer\Dealer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreDealersManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('create', Dealer::class)->allowed();
    }

    public function rules(): array
    {
        return [
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

            'branches' => ['required', 'array', 'min:1'],
            'branches.*.client_key' => ['required', 'string', 'max:100', 'distinct'],
            'branches.*.name' => ['required', 'string', 'max:255'],
            'branches.*.country_id' => ['nullable', 'string', Rule::exists('location_countries', 'id')],
            'branches.*.state_id' => ['nullable', 'string', Rule::exists('location_states', 'id')],
            'branches.*.city_id' => ['nullable', 'string', Rule::exists('location_cities', 'id')],
            'branches.*.suburb_id' => ['required', 'string', Rule::exists('location_suburbs', 'id')],
            'branches.*.contact_numbers' => ['required', 'string', 'max:255'],
            'branches.*.display_address' => ['required', 'string', 'max:255'],
            'branches.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'branches.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'dealer_users' => ['nullable', 'array'],
            'dealer_users.*.firstname' => ['required', 'string', 'max:255'],
            'dealer_users.*.lastname' => ['required', 'string', 'max:255'],
            'dealer_users.*.email' => ['required', 'email', 'max:255', 'unique:dealer_users,email'],

            'sales_people' => ['nullable', 'array'],
            'sales_people.*.branch_client_key' => ['required', 'string', 'max:100'],
            'sales_people.*.firstname' => ['required', 'string', 'max:255'],
            'sales_people.*.lastname' => ['required', 'string', 'max:255'],
            'sales_people.*.contact_no' => ['required', 'string', 'max:255'],
            'sales_people.*.email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
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
    }
}
