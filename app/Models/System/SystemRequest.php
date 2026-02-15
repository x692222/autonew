<?php

namespace App\Models\System;

use App\Enums\SystemRequestStatusEnum;
use App\Traits\HasUuidPrimaryKey;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use App\Traits\HasActivityTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SystemRequest extends Model
{
    use HasUuidPrimaryKey;

    use HasActivityTrait;

    const REQUEST_TYPES = [
        'contact',
        'system',
        'other',
        'feature_tag',
    ];

    protected $table = 'system_requests';

    protected $fillable = [
        'user_id',
        'dealer_user_id',
        'requestable_type',
        'requestable_id',
        'type',
        'subject',
        'message',
        'status',
        'response',
    ];

    protected $hidden = [

    ];

    protected function casts(): array
    {
        return [
            'status' => SystemRequestStatusEnum::class,
        ];
    }

    // observers

    // scopes

    // relationships

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

    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('status', SystemRequestStatusEnum::SUBMITTED->value);
    }
}
