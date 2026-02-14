<?php

namespace App\Models\Dealer;

use App\Traits\HasUuidPrimaryKey;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class DealerUserNotification extends Model
{
    use HasUuidPrimaryKey;

    use SoftDeletes;

    protected $table = 'dealer_user_notifications';

    protected $fillable = [
        'dealer_user_id',
        'target_type',
        'target_id',
        'route_name',
        'route_params',
        'title',
        'description',
        'meta',
        'read_at',
        'dismissed_at',
    ];

    protected $casts = [
        'route_params' => 'array',
        'meta' => 'array',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

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

    /**
     * Convenience: resolve the target model (without polymorphic relationship).
     * Returns null if target_id is null or record not found.
     */
    public function target(): ?Model
    {
        if (!$this->target_id || !class_exists($this->target_type)) {
            return null;
        }

        $class = $this->target_type;

        if (!is_subclass_of($class, Model::class)) {
            return null;
        }

        return $class::query()->find($this->target_id);
    }

    public function markAsRead(): void
    {
        if ($this->read_at) {
            return;
        }

        $this->forceFill(['read_at' => now()])->save();
    }

    public function dismiss(): void
    {
        if ($this->dismissed_at) {
            return;
        }

        $this->forceFill(['dismissed_at' => now()])->save();
    }
}
