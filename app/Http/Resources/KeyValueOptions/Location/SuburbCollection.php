<?php

namespace App\Http\Resources\KeyValueOptions\Location;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SuburbCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return SuburbResource::collection($this->collection)->toArray($request);
    }
}
