<?php

namespace App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Stock;
use App\Models\Stock\Stock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ShowDealerConfigurationStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        /** @var Stock $stock */
        $stock = $this->route('stock');

        return (bool) $actor && Gate::forUser($actor)->inspect('dealerConfigurationShowStock', $stock)->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
