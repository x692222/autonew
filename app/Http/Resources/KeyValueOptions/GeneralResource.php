<?php

namespace App\Http\Resources\KeyValueOptions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'label' => $this['label'],
            'value' => $this['value'],
        ];
    }
}
