<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Quotations;

use App\Models\Dealer\Dealer;
use App\Models\Quotation\Quotation;
use App\Support\Quotations\QuotationValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateDealerQuotationsRequest extends FormRequest
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
        return app(QuotationValidationRules::class)->upsert(
            false,
            $this->route('dealer')?->id,
            $this->route('quotation')?->id,
            false
        );
    }
}
