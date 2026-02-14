<?php

namespace App\Models\System\Configuration;

use App\Traits\HasActivityTrait;
use Illuminate\Database\Eloquent\Model;

class SiteConfiguration extends Model
{
    use HasActivityTrait;

    protected $table = 'site_configurations';

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
}
