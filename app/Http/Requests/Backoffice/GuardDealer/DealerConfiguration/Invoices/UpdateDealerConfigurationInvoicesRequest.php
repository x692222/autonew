<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Invoices;

use App\Models\Invoice\Invoice;
use App\Support\Validation\Invoices\InvoiceValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateDealerConfigurationInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        $invoice = $this->route('invoice');

        return $invoice instanceof Invoice
            && Gate::forUser($actor)->inspect('dealerConfigurationEditInvoice', $invoice)->allowed();
    }

    public function rules(): array
    {
        return app(InvoiceValidationRules::class)->upsert(
            false,
            $this->user('dealer')?->dealer_id,
            $this->route('invoice')?->id,
            false
        );
    }
}
