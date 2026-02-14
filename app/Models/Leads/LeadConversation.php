<?php

namespace App\Models\Leads;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Dealer\Dealer;
use App\ModelScopes\FilterSearchScope;
use App\Traits\HasNotes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadConversation extends Model
{
    use HasUuidPrimaryKey;

    use SoftDeletes;
    use HasNotes;
    use FilterSearchScope;

    protected $table = 'lead_conversations';

    protected $guarded = [];

    protected $casts = [
        'last_message_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LeadMessage::class, 'conversation_id');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(LeadMessage::class, 'last_message_id');
    }

    public function channelable(): MorphTo
    {
        return $this->morphTo();
    }
}
