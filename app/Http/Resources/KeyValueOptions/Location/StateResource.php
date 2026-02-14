<?php

namespace App\Http\Resources\KeyValueOptions\Location;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'label'      => $this['label'],
            'value'      => $this['value'],
            'country_id' => $this['country_id'] ?? null,
        ];
    }
}
