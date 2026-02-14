<?php

namespace App\Models\Stock;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\Leads\Lead;
use App\ModelScopes\FilterSearchScope;
use App\Traits\HasActivityTrait;
use App\Traits\HasNotes;
use App\Traits\InternalReferenceTrait;
use App\Traits\SluggableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Stock extends Model implements HasMedia
{
    use HasUuidPrimaryKey;

    use InteractsWithMedia;
    use SoftDeletes;
    use HasActivityTrait;
    use SluggableTrait;
    use HasNotes;
    use InternalReferenceTrait;
    use FilterSearchScope;

    const STOCK_TYPE_OPTIONS = [
        self::STOCK_TYPE_VEHICLE,
        self::STOCK_TYPE_MOTORBIKE,
        self::STOCK_TYPE_LEISURE,
        self::STOCK_TYPE_COMMERCIAL,
        self::STOCK_TYPE_GEAR,
    ];

    const STOCK_TYPE_VEHICLE    = 'vehicle';
    const STOCK_TYPE_MOTORBIKE  = 'motorbike';
    const STOCK_TYPE_LEISURE    = 'leisure';
    const STOCK_TYPE_COMMERCIAL = 'commercial';
    const STOCK_TYPE_GEAR       = 'gear';

    // Image conversion specs (shared)
    const IMAGE_THUMB_CROP_WIDTH  = 300;
    const IMAGE_THUMB_CROP_HEIGHT = 300;

    const IMAGE_MEDIUM_CROP_WIDTH  = 800;
    const IMAGE_MEDIUM_CROP_HEIGHT = 600;

    const IMAGE_CROP_QUALITY = 80;

    protected $table = 'stock';

    protected $fillable = [
        'branch_id',
        'is_active',
        'is_sold',
        'published_at',
        'internal_reference',
        'type',
        'name',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_new'    => 'boolean',
            'is_sold'   => 'boolean',
        ];
    }

    // scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('is_new', true);
    }

    public function scopeSecondHand(Builder $query): Builder
    {
        return $query->where('is_new', false);
    }

    public function scopeTypeVehicle(Builder $query): Builder
    {
        return $query->where('type', self::STOCK_TYPE_VEHICLE);
    }

    public function scopeTypeMotorbike(Builder $query): Builder
    {
        return $query->where('type', self::STOCK_TYPE_MOTORBIKE);
    }

    public function scopeTypeLeisure(Builder $query): Builder
    {
        return $query->where('type', self::STOCK_TYPE_LEISURE);
    }

    public function scopeTypeCommercial(Builder $query): Builder
    {
        return $query->where('type', self::STOCK_TYPE_COMMERCIAL);
    }

    public function scopeTypeGear(Builder $query): Builder
    {
        return $query->where('type', self::STOCK_TYPE_GEAR);
    }

    // relationships

    public function branch(): BelongsTo
    {
        return $this->belongsTo(DealerBranch::class, 'branch_id');
    }

    public function dealer(): HasOneThrough
    {
        return $this->hasOneThrough(
            Dealer::class,
            DealerBranch::class,
            'id',
            'id',
            'branch_id',
            'dealer_id'
        );
    }

    public function vehicleItem(): HasOne
    {
        return $this->hasOne(StockTypeVehicle::class, 'stock_id');
    }

    public function gearItem(): HasOne
    {
        return $this->hasOne(StockTypeGear::class, 'stock_id');
    }

    public function leisureItem(): HasOne
    {
        return $this->hasOne(StockTypeLeisure::class, 'stock_id');
    }

    public function commercialItem(): HasOne
    {
        return $this->hasOne(StockTypeCommercial::class, 'stock_id');
    }

    public function motorbikeItem(): HasOne
    {
        return $this->hasOne(StockTypeMotorbike::class, 'stock_id');
    }

    public function publishLogs(): HasMany
    {
        return $this->hasMany(StockPublishLog::class, 'stock_id');
    }

    public function impressions(): HasMany
    {
        return $this->hasMany(StockView::class, 'stock_id')->where('type', StockView::VIEW_TYPE_IMPRESSION);
    }

    public function views(): HasMany
    {
        return $this->hasMany(StockView::class, 'stock_id')->where('type', StockView::VIEW_TYPE_IMPRESSION);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(StockFeatureTag::class, 'stock_features', 'stock_id', 'feature_id');
    }

    public function leads()
    {
        return $this->belongsToMany(Lead::class, 'lead_stock')->withTimestamps();
    }

    public function viewSummaries(): HasMany
    {
        return $this->hasMany(StockViewSummary::class, 'stock_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('stock_images')
            ->useDisk(config('media-library.disk_name'));
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->format('jpg')
            ->quality(self::IMAGE_CROP_QUALITY)
            ->fit(Fit::Crop, self::IMAGE_THUMB_CROP_WIDTH, self::IMAGE_THUMB_CROP_HEIGHT)
            ->performOnCollections('stock_images');

        $this->addMediaConversion('medium')
            ->format('jpg')
            ->quality(self::IMAGE_CROP_QUALITY)
            ->fit(Fit::Crop, self::IMAGE_MEDIUM_CROP_WIDTH, self::IMAGE_MEDIUM_CROP_HEIGHT)
            ->performOnCollections('stock_images');
    }

    // functions

    public function isLive(Stock $stock): bool
    {
        // must have published_at set and in the past
        if (!$this->published_at) {
            return false;
        }

        $publishedAt = $this->published_at instanceof Carbon ? $this->published_at : Carbon::parse($this->published_at);

        if ($publishedAt->isFuture()) {
            return false;
        }

        // dealer must be active
        $dealer = $stock->branch->dealer;
        if (!$dealer || !$dealer->is_active) {
            return false;
        }

        // stock item must NOT be sold
        if ((bool) $this->is_sold) {
            return false;
        }

        // must have at least 8 images
        $count = $stock->stock_images_count;

        // Allow common preloaded count attribute name too (if caller didn't pass)
        if ($count === null && isset($this->stock_images_count)) {
            $count = (int) $this->stock_images_count;
        }

        if ($count === null) {
            // only now do the actual count
            $count = $this->getMedia('stock_images')->count();
        }

        return $count >= 1;
    }
}
