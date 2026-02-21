<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\Quotations;

use App\Models\Quotation\Quotation;
use App\Support\Validation\Quotations\QuotationValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class IndexSystemQuotationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::inspect('viewAny', Quotation::class)->allowed();
    }

    public function rules(): array
    {
        return app(QuotationValidationRules::class)->index();
    }
}
