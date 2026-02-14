<?php

namespace App\Models\Leads;

use App\Models\Dealer\Dealer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LeadConversationSummary extends Model
{
    protected $table = 'lead_conversation_summaries';

    protected $fillable = [
        'dealer_id',
        'conversation_id',
        'last_lead_message_id',
        'channel',
        'channelable_type',
        'channelable_id',
        'period_start',
        'period_end',
        'summary_full',
        'summary_delta',
        'meta',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'meta' => 'array',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(LeadConversation::class, 'conversation_id');
    }

    public function lastLeadMessage(): BelongsTo
    {
        return $this->belongsTo(LeadMessage::class, 'last_lead_message_id');
    }

    public function channelable(): MorphTo
    {
        return $this->morphTo();
    }
}
