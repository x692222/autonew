<?php

namespace App\Models\Stock;

use App\Traits\HasUuidPrimaryKey;

use App\Traits\HasActivityTrait;
use App\Traits\SluggableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockView extends Model
{
    use HasUuidPrimaryKey;


    use SoftDeletes;
    use HasActivityTrait;
    use SluggableTrait;

    const VIEW_TYPE_IMPRESSION = 'impression';
    const VIEW_TYPE_DETAIL = 'detail';

    protected $table = 'stock_views';

    protected $fillable = [
        'stock_id',
        'is_sold',
        'ip_address',
        'type',
        'country',
    ];

    protected $hidden = [

    ];

    protected function casts(): array
    {
        return [
            'is_sold' => 'boolean',
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
