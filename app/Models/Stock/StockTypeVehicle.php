<?php

namespace App\Models\Stock;

use App\Traits\HasUuidPrimaryKey;

use App\Traits\HasActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTypeVehicle extends Model
{
    use HasUuidPrimaryKey;


    use SoftDeletes;
    use HasActivityTrait;

    const CATEGORY_SUV       = 'suv';
    const CATEGORY_HATCHBACK = 'hatchback';
    const CATEGORY_SEDAN     = 'sedan';
    const CATEGORY_BUS       = 'bus';
    const CATEGORY_MPV       = 'mpv';
    const CATEGORY_COUPE     = 'coupe';
    const CATEGORY_CABRIOLET = 'cabriolet';
    const CATEGORY_BAKKIE    = 'bakkie';

    const CATEGORY_OPTIONS = [
        self::CATEGORY_SUV,
        self::CATEGORY_HATCHBACK,
        self::CATEGORY_SEDAN,
        self::CATEGORY_BUS,
        self::CATEGORY_MPV,
        self::CATEGORY_COUPE,
        self::CATEGORY_CABRIOLET,
        self::CATEGORY_BAKKIE,
    ];

    const COLOR_OPTIONS = [
        'beige',
        'black',
        'blue',
        'bronze',
        'brown',
        'gold',
        'green',
        'grey',
        'maroon',
        'orange',
        'pink',
        'purple',
        'red',
        'silver',
        'white',
        'yellow',
        'other',
    ];

    const CONDITION_OPTION_NEW     = 'new';
    const CONDITION_OPTION_USED    = 'used';
    const CONDITION_OPTION_REBUILD = 'rebuild';
    const CONDITION_OPTION_DEMO    = 'demo';

    const CONDITION_OPTIONS = [
        self::CONDITION_OPTION_NEW,
        self::CONDITION_OPTION_USED,
        self::CONDITION_OPTION_REBUILD,
        self::CONDITION_OPTION_DEMO
    ];

    const GEARBOX_TYPE_MANUAL    = 'manual';
    const GEARBOX_TYPE_AUTOMATIC = 'automatic';
    const GEARBOX_TYPE_OPTIONS   = [
        self::GEARBOX_TYPE_MANUAL,
        self::GEARBOX_TYPE_AUTOMATIC
    ];

    const FUEL_TYPE_DIESEL   = 'diesel';
    const FUEL_TYPE_PETROL   = 'petrol';
    const FUEL_TYPE_ELECTRIC = 'electric';
    const FUEL_TYPE_HYBRID   = 'hybrid';
    const FUEL_TYPE_OPTIONS  = [
        self::FUEL_TYPE_DIESEL,
        self::FUEL_TYPE_PETROL,
        self::FUEL_TYPE_ELECTRIC,
        self::FUEL_TYPE_HYBRID
    ];

    const DRIVE_TYPE_AWD     = 'awd';
    const DRIVE_TYPE_FWD     = 'fwd';
    const DRIVE_TYPE_RWD     = 'rwd';
    const DRIVE_TYPE_4X4     = '4x4';
    const DRIVE_TYPE_OPTIONS = [
        self::DRIVE_TYPE_AWD,
        self::DRIVE_TYPE_FWD,
        self::DRIVE_TYPE_RWD,
        self::DRIVE_TYPE_4X4
    ];

    protected $table = 'stock_type_vehicles';

    protected $fillable = [
        'stock_id',
        'make_id',
        'model_id',
        'is_import',
        'year_model',
        'category',
        'color',
        'condition',
        'gearbox_type',
        'fuel_type',
        'drive_type',
        'millage',
        'number_of_seats',
        'number_of_doors',
    ];

    protected $hidden = [

    ];

    protected function casts(): array
    {
        return [
            'is_import' => 'boolean',
        ];
    }

    // observers

    // scopes

    // relationships
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(StockMake::class, 'make_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(StockModel::class, 'model_id');
    }
}
