<?php

namespace App\Models\System;

use App\Traits\HasUuidPrimaryKey;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\ModelScopes\FilterSearchScope;
use App\Models\Quotation\Quotation;
use App\Notifications\Auth\BackofficeResetPasswordNotification;
use App\Traits\HasActivityTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasUuidPrimaryKey;

    use HasFactory, Notifiable;
    use SoftDeletes;
    use HasActivityTrait;
    use HasRoles;
    use HasPermissions;
    use FilterSearchScope;

    protected $guard_name = 'backoffice';

    protected $table = 'users';

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

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'is_active',
        'firstname',
        'lastname',
        'email',
        'email_verified_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new BackofficeResetPasswordNotification($token));
    }

    public function createdQuotations(): MorphMany
    {
        return $this->morphMany(Quotation::class, 'created_by');
    }

    public function updatedQuotations(): MorphMany
    {
        return $this->morphMany(Quotation::class, 'updated_by');
    }
}
