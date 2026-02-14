<?php

namespace App\Http\Requests\Backoffice\DealerConfiguration;

use Illuminate\Foundation\Http\FormRequest;

class EditDealershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('dealer');
    }

    public function rules(): array
    {
        return [];
    }
}
