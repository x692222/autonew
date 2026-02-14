<?php

namespace App\Http\Resources\KeyValueOptions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GeneralCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return GeneralResource::collection($this->collection)->toArray($request);
    }
}
