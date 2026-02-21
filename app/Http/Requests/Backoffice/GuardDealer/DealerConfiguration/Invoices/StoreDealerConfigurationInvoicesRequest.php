<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Invoices;

use App\Support\Validation\Invoices\InvoiceValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDealerConfigurationInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        $dealer = $actor?->dealer;

        return (bool) $dealer
            && Gate::forUser($actor)->inspect('dealerConfigurationCreateInvoice', $dealer)->allowed();
    }

    public function rules(): array
    {
        return app(InvoiceValidationRules::class)->upsert(
            false,
            $this->user('dealer')?->dealer_id,
            null
        );
    }
}
