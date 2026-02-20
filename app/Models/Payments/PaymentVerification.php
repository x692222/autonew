<?php

namespace App\Models\Payments;

use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentVerification extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'payment_verifications';

    protected $fillable = [
        'payment_id',
        'amount_verified',
        'date_verified',
        'verified_by_type',
        'verified_by_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_verified' => 'decimal:2',
            'date_verified' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function verifiedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function verifiedByLabel(): ?string
    {
        $user = $this->verifiedBy;

        if (! $user) {
            return $this->verified_by_id ? ('User #'.$this->verified_by_id) : null;
        }

        $firstname = trim((string) ($user->firstname ?? ''));
        $lastname = trim((string) ($user->lastname ?? ''));
        $fullname = trim($firstname . ' ' . $lastname);

        if ($fullname !== '') {
            return $fullname;
        }

        return $user->email ?? null;
    }

    public function verifiedByGuardLabel(): string
    {
        return match ($this->verified_by_type) {
            \App\Models\System\User::class => 'BACKOFFICE',
            \App\Models\Dealer\DealerUser::class => 'DEALER',
            default => 'UNKNOWN',
        };
    }
}
