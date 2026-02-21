<?php

namespace App\Http\Resources\KeyValueOptions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class NoteAuthorCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return NoteAuthorResource::collection($this->collection)->toArray($request);
    }
}
