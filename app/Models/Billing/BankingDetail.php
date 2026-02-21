<?php

namespace App\Models\Billing;

use App\Models\Dealer\Dealer;
use App\Models\Payments\Payment;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankingDetail extends Model
{
    use HasUuidPrimaryKey;
    use SoftDeletes;

    protected $table = 'banking_details';

    protected $fillable = [
        'dealer_id',
        'bank',
        'account_holder',
        'account_number',
        'branch_name',
        'branch_code',
        'swift_code',
        'other_details',
    ];

    public function scopeSystem(Builder $query): Builder
    {
        return $query->whereNull('dealer_id');
    }

    public function scopeForDealer(Builder $query, string $dealerId): Builder
    {
        return $query->where('dealer_id', $dealerId);
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'banking_detail_id');
    }
}
