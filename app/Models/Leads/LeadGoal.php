<?php

namespace App\Models\Leads;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadGoal extends Model
{
    use HasUuidPrimaryKey;

    use SoftDeletes;

    protected $table = 'lead_goals';

    protected $fillable = [
        'lead_id',
        'title',
        'description',
        'created_by_dealer_user_id',
        'created_by_backoffice_user_id',
        'achieved_at',
        'achieved_by_dealer_user_id',
        'meta',
    ];

    protected $casts = [
        'achieved_at' => 'datetime',
        'meta' => 'array',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function createdByDealerUser(): BelongsTo
    {
        return $this->belongsTo(DealerUser::class, 'created_by_dealer_user_id');
    }

    public function createdByBackofficeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_backoffice_user_id');
    }

    public function achievedByDealerUser(): BelongsTo
    {
        return $this->belongsTo(DealerUser::class, 'achieved_by_dealer_user_id');
    }

    public function dealer(): HasOneThrough
    {
        return $this->hasOneThrough(
            Dealer::class,
            Lead::class,
            'id',
            'id',
            'lead_id',
            'dealer_id'
        );
    }
}
