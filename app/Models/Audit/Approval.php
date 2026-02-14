<?php

namespace App\Models\Audit;

use App\Traits\HasUuidPrimaryKey;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Approval extends Model
{
    use HasUuidPrimaryKey;


    use SoftDeletes;

    protected $fillable = [

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
