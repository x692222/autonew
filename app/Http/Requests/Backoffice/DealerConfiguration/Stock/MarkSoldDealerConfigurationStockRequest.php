<?php

namespace App\Http\Requests\Backoffice\DealerConfiguration\Stock;

use App\Models\Stock\Stock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class MarkSoldDealerConfigurationStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('dealer');
        /** @var Stock $stock */
        $stock = $this->route('stock');

        return (bool) $actor && Gate::forUser($actor)->inspect('dealerConfigurationEditStock', $stock)->allowed();
    }

    public function rules(): array
    {
        return [
            'is_sold' => ['required', 'boolean'],
        ];
    }
}
