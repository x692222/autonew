<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Invoices;

use App\Models\Dealer\Dealer;
use App\Support\Invoices\InvoiceValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDealerInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dealer = $this->route('dealer');

        return $dealer instanceof Dealer
            && Gate::inspect('createInvoice', $dealer)->allowed();
    }

    public function rules(): array
    {
        return app(InvoiceValidationRules::class)->upsert(
            false,
            $this->route('dealer')?->id,
            null
        );
    }
}
