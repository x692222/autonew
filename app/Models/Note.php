<?php

namespace App\Models;

use App\Traits\HasUuidPrimaryKey;

use App\ModelScopes\FilterSearchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use HasUuidPrimaryKey;

    use SoftDeletes;
    use FilterSearchScope;

    protected $fillable = [
        'note',
        'backoffice_only',
        'author_type',
        'author_id',
    ];

    protected $casts = [
        'backoffice_only' => 'bool',
    ];

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): MorphTo
    {
        return $this->morphTo();
    }
}
