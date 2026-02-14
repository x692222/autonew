<?php

namespace App\Support\Services;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaHelper
{
    public static function mediaPayload(Media $m): array // @todo move
    {
        $original = $m->getUrl();

        $thumb  = $original;
        $medium = $original;

        if ($m->hasGeneratedConversion('thumb')) {
            $thumb = $m->getUrl('thumb');
        }

        if ($m->hasGeneratedConversion('medium')) {
            $medium = $m->getUrl('medium');
        }

        return [
            'id'           => $m->getKey(),
            'name'         => $m->file_name,
            'created_at'   => optional($m->created_at)->toDateTimeString(),
            'thumb_url'    => $thumb,
            'medium_url'   => $medium,
            'original_url' => $original,
        ];
    }
}
