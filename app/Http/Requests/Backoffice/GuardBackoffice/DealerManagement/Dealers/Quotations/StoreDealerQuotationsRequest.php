<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Quotations;

use App\Models\Dealer\Dealer;
use App\Support\Quotations\QuotationValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDealerQuotationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dealer = $this->route('dealer');

        return $dealer instanceof Dealer
            && Gate::inspect('createQuotation', $dealer)->allowed();
    }

    public function rules(): array
    {
        return app(QuotationValidationRules::class)->upsert(
            false,
            $this->route('dealer')?->id,
            null
        );
    }
}
