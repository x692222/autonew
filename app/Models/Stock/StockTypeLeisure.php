<?php

namespace App\Models\Stock;

use App\Traits\HasUuidPrimaryKey;

use App\Traits\HasActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTypeLeisure extends Model
{
    use HasUuidPrimaryKey;


    use SoftDeletes;
    use HasActivityTrait;

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

    protected $table = 'stock_type_leisure';

    protected $fillable = [
        'stock_id',
        'make_id',
        'year_model',
        'color',
        'condition',
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
}
