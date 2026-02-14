<?php

namespace App\Models\Stock;

use App\Traits\HasActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockPublishLog extends Model
{

    use SoftDeletes;

    const ACTION_PUBLISH   = 1;
    const ACTION_UNPUBLISH = 0;

    protected $table = 'stock_publish_logs';

    protected $fillable = [
        'stock_id',
        'action',
        'by_user_id', // @todo relationship
    ];

    protected $hidden = [

    ];

    protected function casts(): array
    {
        return [
            'type' => 'boolean',
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
