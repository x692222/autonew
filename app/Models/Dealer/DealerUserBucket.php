<?php

namespace App\Models\Dealer;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Dealer\Dealer;
use App\Models\Stock\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DealerUserBucket extends Model implements HasMedia
{
    use HasUuidPrimaryKey;

    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'dealer_user_buckets';

    protected $fillable = [
        'user_id',
        'name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(DealerUser::class, 'user_id');
    }

    public function dealer(): HasOneThrough
    {
        return $this->hasOneThrough(
            Dealer::class,
            DealerUser::class,
            'id',
            'id',
            'user_id',
            'dealer_id'
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('dealer_user_bucket')
            ->useDisk(config('media-library.disk_name'));
    }

    public function registerMediaConversions(Media $media = null): void
    {
        // thumb: 300x400 center crop
        $this->addMediaConversion('thumb')
            ->format('jpg')
            ->quality(80)
            ->fit(Fit::Crop, Stock::IMAGE_THUMB_CROP_WIDTH, Stock::IMAGE_THUMB_CROP_HEIGHT)
            ->performOnCollections('dealer_user_bucket');

        // medium: 800x600 center crop
        $this->addMediaConversion('medium')
            ->format('jpg')
            ->quality(80)
            ->fit(Fit::Crop, Stock::IMAGE_MEDIUM_CROP_WIDTH, Stock::IMAGE_MEDIUM_CROP_HEIGHT)
            ->performOnCollections('dealer_user_bucket');
    }
}
