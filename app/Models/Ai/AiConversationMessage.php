<?php

namespace App\Models\Ai;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Dealer\Dealer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiConversationMessage extends Model
{
    use HasUuidPrimaryKey;

    use SoftDeletes;

    protected $table = 'ai_conversation_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'name',
        'content',
        'tool_calls',
        'tool_call_id',
        'tokens_in',
        'tokens_out',
        'total_tokens',
        'meta',
    ];

    protected $casts = [
        'tool_calls' => 'array',
        'meta' => 'array',
        'tokens_in' => 'integer',
        'tokens_out' => 'integer',
        'total_tokens' => 'integer',
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
