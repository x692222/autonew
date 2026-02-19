<?php

namespace App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock;
use App\Models\Dealer\Dealer;
use App\Models\Stock\Stock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DestroyDealerStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Dealer $dealer */
        $dealer = $this->route('dealer');
        /** @var Stock $stock */
        $stock = $this->route('stock');

        return Gate::inspect('deleteStock', [$dealer, $stock])->allowed();
    }

    public function rules(): array
    {
        return [];
    }
}
