<?php

namespace App\Models\Stock;

use App\Traits\HasUuidPrimaryKey;

use App\Traits\HasActivityTrait;
use App\Traits\SluggableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockModel extends Model
{
    use HasUuidPrimaryKey;


    use SoftDeletes;
    use HasActivityTrait;
    use SluggableTrait;

    protected $table = 'stock_models';

    protected $fillable = [
        'make_id',
        'name',
        'slug',
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

    public function make(): BelongsTo
    {
        return $this->belongsTo(StockMake::class, 'make_id');
    }
}
