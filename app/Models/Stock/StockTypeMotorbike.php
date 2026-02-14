<?php

namespace App\Models\Stock;

use App\Traits\HasActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTypeMotorbike extends Model
{

    use SoftDeletes;
    use HasActivityTrait;

    const CATEGORY_ROAD       = 'road';
    const CATEGORY_DUAL_PURPOSE = 'dual_purpose';
    const CATEGORY_OFF_ROAD     = 'off_road';
    const CATEGORY_SCOOTER       = 'scooter';
    const CATEGORY_FOUR_WHEEL       = 'four_wheel';
    const CATEGORY_SPORT     = 'sport';
    const CATEGORY_THREE_WHEEL = 'three_wheel';
    const CATEGORY_SIDE_BY_SIDE    = 'side_by_side';
    const CATEGORY_MOTO_CROSS    = 'moto_cross';

    const CATEGORY_OPTIONS = [
        self::CATEGORY_ROAD,
        self::CATEGORY_DUAL_PURPOSE,
        self::CATEGORY_OFF_ROAD,
        self::CATEGORY_SCOOTER,
        self::CATEGORY_FOUR_WHEEL,
        self::CATEGORY_SPORT,
        self::CATEGORY_THREE_WHEEL,
        self::CATEGORY_SIDE_BY_SIDE,
        self::CATEGORY_MOTO_CROSS,
    ];

    const COLOR_OPTIONS = [
        'black',
        'blue',
        'green',
        'grey',
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

    const FUEL_TYPE_PETROL   = 'petrol';
    const FUEL_TYPE_ELECTRIC = 'electric';
    const FUEL_TYPE_OPTIONS  = [
        self::FUEL_TYPE_PETROL,
        self::FUEL_TYPE_ELECTRIC,
    ];

    protected $table = 'stock_type_motorbikes';

    protected $fillable = [
        'stock_id',
        'make_id',
        'year_model',
        'category',
        'color',
        'condition',
        'millage',
        'gearbox_type',
        'fuel_type',
    ];

    protected $hidden = [

    ];

    protected function casts(): array
    {
        return [

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
        return $this->belongsTo(StockModel::class, 'make_id');
    }
}
