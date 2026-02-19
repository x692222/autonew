<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\Quotations;

use App\Models\Quotation\Quotation;
use App\Support\Quotations\QuotationValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreSystemQuotationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('create', Quotation::class)->allowed();
    }

    public function rules(): array
    {
        return app(QuotationValidationRules::class)->upsert(true, null, null);
    }
}
