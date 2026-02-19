<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\System\Quotations;

use App\Models\Quotation\Quotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class EditSystemQuotationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quotation = $this->route('quotation');

        return $quotation instanceof Quotation
            && Gate::inspect('update', $quotation)->allowed();
    }

    public function rules(): array
    {
        return [
            'return_to' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
