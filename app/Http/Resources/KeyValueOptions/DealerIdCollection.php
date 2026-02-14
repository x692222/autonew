<?php

namespace App\Http\Resources\KeyValueOptions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DealerIdCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return DealerIdResource::collection($this->collection)->toArray($request);
    }
}
