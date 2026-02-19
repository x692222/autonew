<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Quotations;

use App\Models\Dealer\Dealer;
use App\Models\Quotation\Quotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class EditDealerQuotationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dealer = $this->route('dealer');
        $quotation = $this->route('quotation');

        return $dealer instanceof Dealer
            && $quotation instanceof Quotation
            && Gate::inspect('editQuotation', [$dealer, $quotation])->allowed();
    }

    public function rules(): array
    {
        return [
            'return_to' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
