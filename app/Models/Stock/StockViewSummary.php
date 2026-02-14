<?php

namespace App\Models\Stock;

use App\Models\Dealer\Dealer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockViewSummary extends Model
{
    protected $table = 'stock_view_summary';

    protected $fillable = [
        'dealer_id',
        'stock_id',
        'type',
        'country',
        'total_views',
        'date',
    ];

    protected $casts = [
        'total_views' => 'integer',
        'date' => 'date',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
}
