<?php

namespace App\Models\Media;

use App\Traits\HasUuidPrimaryKey;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class Media extends SpatieMedia
{
    use HasUuidPrimaryKey;
}

