<?php

namespace App\Http\Resources\Backoffice\Shared\QuotationLookups;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LineItemHistorySuggestionCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return LineItemHistorySuggestionResource::collection($this->collection)->toArray($request);
    }
}
