<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Invoices;

use App\Models\Dealer\Dealer;
use App\Models\Invoice\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DestroyDealerInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dealer = $this->route('dealer');
        $invoice = $this->route('invoice');

        return $dealer instanceof Dealer
            && $invoice instanceof Invoice
            && Gate::inspect('deleteInvoice', [$dealer, $invoice])->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
