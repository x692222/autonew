<?php

namespace App\Http\Resources\Backoffice\Shared\QuotationLookups;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LineItemHistorySuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'source' => 'history',
            'sku' => $this->resource->sku,
            'description' => $this->resource->description,
            'amount' => (float) $this->resource->amount,
            'qty' => 1,
            'total' => (float) $this->resource->amount,
            'stock_id' => null,
        ];
    }
}
