<?php

namespace App\Support\Services;

use App\Models\Dealer\DealerUserBucket;
use App\Models\Stock\Stock;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class ModelIdPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {

        if ($media->model_type === DealerUserBucket::class) {
            /** @var DealerUserBucket|null $bucket */
            $bucket = $media->model;
            $dealerId = $bucket?->dealer_id ?? $media->model_id;

            return "dealers/{$dealerId}/bucket/";
        }

        if ($media->model_type === Stock::class) {
            /** @var Stock|null $stock */
            $stock = $media->model;

            $dealerId = $stock?->branch?->dealer_id;
            $ref = $stock?->internal_reference ?: (string)$stock?->getKey();

            if ($dealerId) {
                $safeRef = Str::slug($ref, '-');
                return "dealers/{$dealerId}/images/{$safeRef}/";
            }
        }

        // Fallback (existing behaviour but interface-compliant)
        $modelFolder = Str::plural(Str::kebab(class_basename($media->model_type)));

        return "{$modelFolder}/{$media->model_id}/";
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . 'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . 'responsive/';
    }
}
