<?php

namespace App\Http\Resources\KeyValueOptions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerLookupCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return CustomerLookupResource::collection($this->collection)->toArray($request);
    }
}
