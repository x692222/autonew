<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Quotations;

use App\Models\Quotation\Quotation;
use App\Support\Validation\Quotations\QuotationValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateDealerConfigurationQuotationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        $quotation = $this->route('quotation');

        return $quotation instanceof Quotation
            && Gate::forUser($actor)->inspect('dealerConfigurationEditQuotation', $quotation)->allowed();
    }

    public function rules(): array
    {
        return app(QuotationValidationRules::class)->upsert(
            false,
            $this->user('dealer')?->dealer_id,
            $this->route('quotation')?->id,
            false
        );
    }
}
