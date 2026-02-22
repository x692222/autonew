<?php

namespace App\Http\Requests\Backoffice\Shared\Customers;

use App\Models\Dealer\Dealer;
use App\Models\Quotation\Customer;
use App\Support\Validation\Customers\CustomerValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpsertCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'contact_number' => preg_replace('/\s+/', '', (string) $this->input('contact_number', '')),
            'vat_number' => preg_replace('/\s+/', '', (string) $this->input('vat_number', '')),
        ]);
    }

    public function rules(): array
    {
        return app(CustomerValidationRules::class)->upsert(
            dealerId: $this->resolveDealerId(),
            ignoreCustomerId: $this->resolveCustomerId(),
        );
    }

    private function resolveDealerId(): ?string
    {
        $dealerRoute = $this->route('dealer');
        if ($dealerRoute instanceof Dealer) {
            return (string) $dealerRoute->id;
        }

        $dealerActor = $this->user('dealer');
        if ($dealerActor?->dealer_id) {
            return (string) $dealerActor->dealer_id;
        }

        return null;
    }

    private function resolveCustomerId(): ?string
    {
        $customerRoute = $this->route('customer');

        if ($customerRoute instanceof Customer) {
            return (string) $customerRoute->id;
        }

        if (is_string($customerRoute) && $customerRoute !== '') {
            return $customerRoute;
        }

        return null;
    }
}
