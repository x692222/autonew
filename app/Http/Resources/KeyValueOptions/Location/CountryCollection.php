<?php

namespace App\Http\Resources\KeyValueOptions\Location;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CountryCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return CountryResource::collection($this->collection)->toArray($request);
    }
}
