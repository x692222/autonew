<?php

namespace App\Models\Stock;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Dealer\DealerUser;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class StockFeatureTag extends Model
{
    use HasUuidPrimaryKey;


    protected $table = 'stock_feature_tags';

    protected $fillable = [
        'is_approved',
        'name',
        'stock_type',
        'requested_by_user_id',
        'requested_by_dealer_user_id',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $hidden = [

    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    // observers

    // scopes

    // relationships

    public function stock(): BelongsToMany
    {
        return $this->belongsToMany(Stock::class, 'stock_features', 'feature_id', 'stock_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function requestedByDealerUser(): BelongsTo
    {
        return $this->belongsTo(DealerUser::class, 'requested_by_dealer_user_id');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('is_approved', false)->whereNull('reviewed_at');
    }
}
