<?php

namespace App\Models\Location;

use App\ModelScopes\FilterSearchScope;
use App\Traits\HasActivityTrait;
use App\Traits\SluggableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocationState extends Model
{

    use SoftDeletes;
    use SluggableTrait;
    use HasActivityTrait;
    use FilterSearchScope;

    protected $table = 'location_states';

    protected $fillable = [
        'country_id',
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

    public function country(): BelongsTo
    {
        return $this->belongsTo(LocationCountry::class, 'country_id');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(LocationCity::class, 'state_id');
    }
}
