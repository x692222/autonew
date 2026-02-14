<?php

namespace App\Models\Dealer;

use App\Models\Dealer\Dealer;
use App\ModelScopes\FilterSearchScope;
use App\Traits\HasActivityTrait;
use App\Traits\HasNotes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class DealerSalePerson extends Model
{

    use SoftDeletes;
    use HasActivityTrait;
    use FilterSearchScope;
    use HasNotes;

    protected $table = 'dealer_sale_people';

    protected $fillable = [
        'branch_id',
        'firstname',
        'lastname',
        'contact_no',
        'email',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(DealerBranch::class, 'branch_id');
    }

    public function dealer(): HasOneThrough
    {
        return $this->hasOneThrough(
            Dealer::class,
            DealerBranch::class,
            'id',
            'id',
            'branch_id',
            'dealer_id'
        );
    }
}
