<?php

namespace App\Models\Leads\Channels;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use App\Models\Leads\LeadMessage;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class WhatsappMessage extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'whatsapp_messages';

    protected $guarded = [];

    protected $casts = [
        'media' => 'array',
        'raw_payload' => 'array',
        'pricing' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function message(): MorphOne
    {
        return $this->morphOne(LeadMessage::class, 'messageable');
    }

    public function dealerUser(): BelongsTo
    {
        return $this->belongsTo(DealerUser::class, 'dealer_user_id');
    }

    public function systemUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'system_user_id');
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
