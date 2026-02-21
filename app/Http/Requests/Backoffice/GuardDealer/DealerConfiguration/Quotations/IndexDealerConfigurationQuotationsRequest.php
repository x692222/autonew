<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Quotations;

use App\Support\Validation\Quotations\QuotationValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexDealerConfigurationQuotationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        $dealer = $actor?->dealer;

        return (bool) $dealer
            && Gate::forUser($actor)->inspect('dealerConfigurationIndexQuotations', $dealer)->allowed();
    }

    public function rules(): array
    {
        return app(QuotationValidationRules::class)->index();
    }
}
