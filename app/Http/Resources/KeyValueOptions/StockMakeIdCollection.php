<?php

namespace App\Http\Resources\KeyValueOptions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StockMakeIdCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return StockMakeIdResource::collection($this->collection)->toArray($request);
    }
}
