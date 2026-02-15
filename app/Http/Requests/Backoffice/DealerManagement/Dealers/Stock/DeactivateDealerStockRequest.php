<?php

namespace App\Http\Requests\Backoffice\DealerManagement\Dealers\Stock;

use App\Models\Dealer\Dealer;
use App\Models\Stock\Stock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeactivateDealerStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        /** @var Stock $stock */
        $stock = $this->route('stock');

        return Gate::inspect('changeStockStatusItem', [$dealer, $stock])->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
