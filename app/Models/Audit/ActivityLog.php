<?php

namespace App\Models\Audit;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasUuidPrimaryKey;


    protected $table = 'activity_logs';

    protected $fillable = [
        'event', 'description', 'properties', 'user_id', 'dealer_user_id', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dealerUser(): BelongsTo
    {
        return $this->belongsTo(DealerUser::class, 'dealer_user_id');
    }

    public function dealer(): HasOneThrough
    {
        return $this->hasOneThrough(
            Dealer::class,
            DealerUser::class,
            'id',
            'id',
            'dealer_user_id',
            'dealer_id'
        );
    }
}
