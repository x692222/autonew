<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Quotations;

use App\Models\Dealer\Dealer;
use App\Support\Validation\Quotations\QuotationValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexDealerQuotationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dealer = $this->route('dealer');

        return $dealer instanceof Dealer
            && Gate::inspect('showQuotations', $dealer)->allowed();
    }

    public function rules(): array
    {
        return app(QuotationValidationRules::class)->index();
    }
}
