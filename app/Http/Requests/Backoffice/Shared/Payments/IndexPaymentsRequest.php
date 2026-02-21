<?php

namespace App\Http\Requests\Backoffice\Shared\Payments;

use App\Support\Validation\Payments\PaymentValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class IndexPaymentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return app(PaymentValidationRules::class)->index();
    }
}
