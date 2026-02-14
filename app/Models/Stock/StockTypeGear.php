<?php

namespace App\Models\Stock;

use App\Traits\HasActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTypeGear extends Model
{

    use SoftDeletes;
    use HasActivityTrait;

    const CONDITION_OPTION_NEW     = 'new';
    const CONDITION_OPTION_USED    = 'used';

    const CONDITION_OPTIONS = [
        self::CONDITION_OPTION_NEW,
        self::CONDITION_OPTION_USED
    ];

    protected $table = 'stock_type_gear';

    protected $fillable = [
        'stock_id',
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
}
