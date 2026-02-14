<?php

namespace App\Models\Ai;

use App\Models\Dealer\Dealer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiConversationContext extends Model
{
    use SoftDeletes;

    protected $table = 'ai_conversation_contexts';

    protected $fillable = [
        'conversation_id',
        'context_type',
        'payload',
        'payload_text',
        'priority',
        'superseded_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'superseded_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    public function dealer(): HasOneThrough
    {
        return $this->hasOneThrough(
            Dealer::class,
            AiConversation::class,
            'id',
            'id',
            'conversation_id',
            'dealer_id'
        );
    }
}
