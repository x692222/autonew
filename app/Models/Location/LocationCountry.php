<?php

namespace App\Models\Location;

use App\ModelScopes\FilterSearchScope;
use App\Traits\HasActivityTrait;
use App\Traits\SluggableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocationCountry extends Model
{

    use SoftDeletes;
    use SluggableTrait;
    use HasActivityTrait;
    use FilterSearchScope;

    protected $table = 'location_countries';

    protected $fillable = [
        'name'
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

    public function states(): HasMany
    {
        return $this->hasMany(LocationState::class, 'country_id');
    }
}
