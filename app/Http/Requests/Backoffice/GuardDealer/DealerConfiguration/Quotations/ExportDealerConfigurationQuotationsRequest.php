<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Quotations;

use App\Models\Quotation\Quotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ExportDealerConfigurationQuotationsRequest extends FormRequest
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
        return [];
    }
}
