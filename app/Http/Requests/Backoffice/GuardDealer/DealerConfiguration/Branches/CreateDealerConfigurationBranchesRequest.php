<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Branches;
use Illuminate\Foundation\Http\FormRequest;

class CreateDealerConfigurationBranchesRequest extends FormRequest
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
