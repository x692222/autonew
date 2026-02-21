<?php

namespace App\Http\Requests\Backoffice\Shared\Customers;

use App\Support\Validation\Customers\CustomerValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class IndexCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return app(CustomerValidationRules::class)->index();
    }
}
