<?php

namespace App\Http\Resources\KeyValueOptions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteAuthorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'label' => self::labelFor($this->resource),
            'value' => $this->resource->id,
        ];
    }

    public static function labelFor(object $author): string
    {
        $firstname = trim((string) ($author->firstname ?? ''));
        $lastname = trim((string) ($author->lastname ?? ''));
        $fullName = trim($firstname . ' ' . $lastname);

        return $fullName !== '' ? $fullName : (string) ($author->email ?? $author->id);
    }
}
