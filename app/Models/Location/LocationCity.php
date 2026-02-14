<?php

namespace App\Models\Location;

use App\Traits\HasUuidPrimaryKey;

use App\ModelScopes\FilterSearchScope;
use App\Traits\HasActivityTrait;
use App\Traits\SluggableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocationCity extends Model
{
    use HasUuidPrimaryKey;


    use SoftDeletes;
    use SluggableTrait;
    use HasActivityTrait;
    use FilterSearchScope;

    protected $table = 'location_cities';

    protected $fillable = [
        'state_id',
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

    public function state(): BelongsTo
    {
        return $this->belongsTo(LocationState::class, 'state_id');
    }

    public function suburbs(): HasMany
    {
        return $this->hasMany(LocationSuburb::class, 'city_id');
    }
}
