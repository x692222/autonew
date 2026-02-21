<?php

namespace App\Http\Requests\Backoffice\Shared\BankingDetails;

use App\Support\Validation\BankingDetails\BankingDetailValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpsertBankingDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return app(BankingDetailValidationRules::class)->upsert();
    }
}
