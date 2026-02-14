<?php

namespace App\Http\Resources\KeyValueOptions\Location;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'label'    => $this['label'],
            'value'    => $this['value'],
            'state_id' => $this['state_id'] ?? null,
        ];
    }
}
