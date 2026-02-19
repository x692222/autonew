<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\Quotations;

use App\Models\Quotation\Quotation;
use App\Support\Quotations\QuotationValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSystemQuotationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quotation = $this->route('quotation');

        return $quotation instanceof Quotation
            && Gate::inspect('update', $quotation)->allowed();
    }

    public function rules(): array
    {
        return app(QuotationValidationRules::class)->upsert(
            true,
            null,
            $this->route('quotation')?->id,
            false
        );
    }
}
