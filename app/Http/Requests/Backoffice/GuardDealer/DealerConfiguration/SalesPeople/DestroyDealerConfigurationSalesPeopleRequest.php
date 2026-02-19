<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\SalesPeople;
use Illuminate\Foundation\Http\FormRequest;

class DestroyDealerConfigurationSalesPeopleRequest extends FormRequest
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
