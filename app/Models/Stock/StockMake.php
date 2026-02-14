<?php

namespace App\Models\Stock;

use App\Traits\HasActivityTrait;
use App\Traits\SluggableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockMake extends Model
{

    use SoftDeletes;
    use HasActivityTrait;
    use SluggableTrait;

    protected $table = 'stock_makes';

    protected $fillable = [
        'name',
        'slug',
        'stock_type',
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

    public function models(): HasMany
    {
        return $this->hasMany(StockModel::class, 'make_id');
    }

    public function vehicleStock(): HasMany
    {
        return $this->hasMany(StockTypeVehicle::class, 'make_id');
    }

    public function motorbikeStock(): HasMany
    {
        return $this->hasMany(StockTypeMotorbike::class, 'make_id');
    }

    public function commercialStock(): HasMany
    {
        return $this->hasMany(StockTypeCommercial::class, 'make_id');
    }

    public function leisureStock(): HasMany
    {
        return $this->hasMany(StockTypeCommercial::class, 'make_id');
    }
}
