<?php

namespace App\Models\Ai;

use App\Models\Dealer\Dealer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiConversation extends Model
{
    use SoftDeletes;

    protected $table = 'ai_conversations';

    protected $fillable = [
        'dealer_id',
        'owner_type',
        'owner_id',
        'purpose',
        'openai_model',
        'max_output_tokens',
        'min_output_tokens',
        'temperature',
        'summary',
        'last_used_at',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'last_used_at' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function contexts(): HasMany
    {
        return $this->hasMany(AiConversationContext::class, 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiConversationMessage::class, 'conversation_id');
    }
}
