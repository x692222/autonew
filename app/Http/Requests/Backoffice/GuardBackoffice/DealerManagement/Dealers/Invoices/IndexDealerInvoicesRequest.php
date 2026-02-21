<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Invoices;

use App\Models\Dealer\Dealer;
use App\Support\Validation\Invoices\InvoiceValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexDealerInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dealer = $this->route('dealer');

        return $dealer instanceof Dealer
            && Gate::inspect('showInvoices', $dealer)->allowed();
    }

    public function rules(): array
    {
        return app(InvoiceValidationRules::class)->index();
    }
}
