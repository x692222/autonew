<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Invoices;

use App\Support\Invoices\InvoiceValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexDealerConfigurationInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        $dealer = $actor?->dealer;

        return (bool) $dealer
            && Gate::forUser($actor)->inspect('dealerConfigurationIndexInvoices', $dealer)->allowed();
    }

    public function rules(): array
    {
        return app(InvoiceValidationRules::class)->index();
    }
}
