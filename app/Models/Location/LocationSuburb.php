<?php

namespace App\Models\Location;

use App\ModelScopes\FilterSearchScope;
use App\Traits\HasActivityTrait;
use App\Traits\SluggableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocationSuburb extends Model
{

    use SoftDeletes;
    use SluggableTrait;
    use HasActivityTrait;
    use FilterSearchScope;

    protected $table = 'location_suburbs';

    protected $fillable = [
        'city_id',
        'name',
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

    public function city(): BelongsTo
    {
        return $this->belongsTo(LocationCity::class, 'city_id');
    }
}
