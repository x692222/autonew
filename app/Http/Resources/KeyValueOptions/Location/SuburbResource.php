<?php

namespace App\Http\Resources\KeyValueOptions\Location;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuburbResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'label'   => $this['label'],
            'value'   => $this['value'],
            'city_id' => $this['city_id'] ?? null,
        ];
    }
}
