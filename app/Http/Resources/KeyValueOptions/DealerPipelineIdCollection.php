<?php

namespace App\Http\Resources\KeyValueOptions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DealerPipelineIdCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return DealerPipelineIdResource::collection($this->collection)->toArray($request);
    }
}
