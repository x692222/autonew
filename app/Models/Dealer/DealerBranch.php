<?php

namespace App\Models\Dealer;

use App\Models\Leads\Lead;
use App\Models\Location\LocationSuburb;
use App\Models\Stock\Stock;
use App\ModelScopes\FilterActiveStatusScope;
use App\ModelScopes\FilterLocationsScope;
use App\ModelScopes\FilterSearchScope;
use App\Traits\HasActivityTrait;
use App\Traits\HasNotes;
use App\Traits\SluggableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DealerBranch extends Model
{

    use SoftDeletes;
    use SluggableTrait;
    use HasActivityTrait;
    use FilterSearchScope;
    use FilterActiveStatusScope;
    use FilterLocationsScope;
    use HasNotes;

    protected $table = 'dealer_branches';

    protected $fillable = [
        'dealer_id',
        'suburb_id',
        'name',
        'contact_numbers',
        'display_address',
        'latitude',
        'longitude',
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

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function suburb(): BelongsTo
    {
        return $this->belongsTo(LocationSuburb::class, 'suburb_id');
    }

    public function salePeople(): HasMany
    {
        return $this->hasMany(DealerSalePerson::class, 'branch_id');
    }

    public function stockItems(): HasMany
    {
        return $this->hasMany(Stock::class, 'branch_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'branch_id');
    }

}
