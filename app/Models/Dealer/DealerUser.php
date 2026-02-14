<?php

namespace App\Models\Dealer;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Leads\Lead;
use App\Models\Order;
use App\ModelScopes\FilterSearchScope;
use App\Notifications\Auth\DealerResetPasswordNotification;
use App\Traits\HasActivityTrait;
use App\Traits\HasNotes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasPermissions;

class DealerUser extends Authenticatable
{
    use HasUuidPrimaryKey;


    use Notifiable;
    use SoftDeletes;
    use HasActivityTrait;
    use HasPermissions;
    use FilterSearchScope;
    use HasNotes;

    protected $guard_name = 'dealer';

    protected $table = 'dealer_users';

    protected $fillable = [
        'dealer_id',
        'is_active',
        'firstname',
        'lastname',
        'email',
        'email_verified_at',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // observers

    public static function booted(): void
    {
        static::saving(function($model) {
            if (!$model->exists) {
                if (empty($model->password)) {
                    $model->password = bcrypt(Str::random(16)); // @todo password notification
                }
            }
        });
    }

    // scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    // relationships

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function bucket(): HasOne
    {
        return $this->hasOne(DealerUserBucket::class, 'user_id');
    }

    public function buckets(): HasMany
    {
        return $this->hasMany(DealerUserBucket::class, 'user_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to_dealer_user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(DealerUserNotification::class, 'dealer_user_id');
    }

    // other

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new DealerResetPasswordNotification($token));
    }
}
