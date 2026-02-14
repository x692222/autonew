<?php

namespace App\Models\Stock;

use App\Traits\HasUuidPrimaryKey;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StockFeatureTag extends Model
{
    use HasUuidPrimaryKey;


    protected $table = 'stock_feature_tags';

    protected $fillable = [
        'is_approved',
        'name',
        'stock_type',
    ];

    protected $hidden = [

    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
        ];
    }

    // observers

    // scopes

    // relationships

    public function stock(): BelongsToMany
    {
        return $this->belongsToMany(Stock::class, 'stock_features', 'feature_id', 'stock_id');
    }
}
