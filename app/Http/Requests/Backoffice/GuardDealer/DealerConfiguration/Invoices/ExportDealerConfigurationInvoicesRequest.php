<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Invoices;

use App\Models\Invoice\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ExportDealerConfigurationInvoicesRequest extends FormRequest
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
        return [];
    }
}
