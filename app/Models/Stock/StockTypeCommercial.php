<?php

namespace App\Models\Stock;

use App\Enums\PoliceClearanceStatusEnum;
use App\Traits\HasUuidPrimaryKey;

use App\Traits\HasActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTypeCommercial extends Model
{
    use HasUuidPrimaryKey;


    use SoftDeletes;
    use HasActivityTrait;

    const COLOR_OPTIONS = [
        'black',
        'blue',
        'grey',
        'red',
        'silver',
        'white',
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

    const POLICE_CLEARANCE_STATUS_OPTIONS = [
        PoliceClearanceStatusEnum::YES->value,
        PoliceClearanceStatusEnum::NO->value,
        PoliceClearanceStatusEnum::UNDEFINED->value,
    ];

    protected $table = 'stock_type_commercial';

    protected $fillable = [
        'stock_id',
        'make_id',
        'year_model',
        'vin_number',
        'engine_number',
        'mm_code',
        'color',
        'condition',
        'gearbox_type',
        'fuel_type',
        'millage',
        'is_police_clearance_ready',
        'registration_date',
    ];

    protected $hidden = [

    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
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
}
