<?php

namespace App\Http\Resources\KeyValueOptions\Location;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StateCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return StateResource::collection($this->collection)->toArray($request);
    }
}
