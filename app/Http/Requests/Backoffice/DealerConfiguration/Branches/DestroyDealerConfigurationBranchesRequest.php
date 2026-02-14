<?php

namespace App\Http\Requests\Backoffice\DealerConfiguration\Branches;

use Illuminate\Foundation\Http\FormRequest;

class DestroyDealerConfigurationBranchesRequest extends FormRequest
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
