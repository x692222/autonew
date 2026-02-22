<?php

namespace App\Models\Payments;

use App\Enums\PaymentMethodEnum;
use App\Models\Billing\BankingDetail;
use App\Models\Dealer\Dealer;
use App\Models\Invoice\Invoice;
use App\Models\Payments\PaymentVerification;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasUuidPrimaryKey;
    use SoftDeletes;

    protected $table = 'payments';

    protected $fillable = [
        'invoice_id',
        'dealer_id',
        'banking_detail_id',
        'payment_method',
        'amount',
        'payment_date',
        'description',
        'is_approved',
        'created_by_type',
        'created_by_id',
        'updated_by_type',
        'updated_by_id',
        'created_from_ip',
        'updated_from_ip',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'payment_method' => PaymentMethodEnum::class,
            'is_approved' => 'boolean',
        ];
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->whereNull('dealer_id');
    }

    public function scopeForDealer(Builder $query, string $dealerId): Builder
    {
        return $query->where('dealer_id', $dealerId);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function bankingDetail(): BelongsTo
    {
        return $this->belongsTo(BankingDetail::class, 'banking_detail_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(PaymentVerification::class, 'payment_id');
    }

    public function latestVerification(): HasOne
    {
        return $this->hasOne(PaymentVerification::class, 'payment_id')
            ->select([
                'payment_verifications.id',
                'payment_verifications.payment_id',
                'payment_verifications.amount_verified',
                'payment_verifications.date_verified',
                'payment_verifications.verified_by_type',
                'payment_verifications.verified_by_id',
                'payment_verifications.created_at',
                'payment_verifications.updated_at',
            ])
            ->latestOfMany('date_verified');
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function updatedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function recordedByLabel(): ?string
    {
        $user = $this->createdBy;

        if (! $user) {
            return null;
        }

        $firstname = trim((string) ($user->firstname ?? ''));
        $lastname = trim((string) ($user->lastname ?? ''));
        $fullname = trim($firstname.' '.$lastname);

        if ($fullname !== '') {
            return $fullname;
        }

        return $user->email ?? null;
    }
}
