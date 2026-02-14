<?php

namespace App\Models\Leads;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerUser;
use App\Models\Stock\Stock;
use App\Models\System\User;
use App\ModelScopes\FilterSearchScope;
use App\Traits\HasNotes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadMessage extends Model
{
    use SoftDeletes;
    use HasNotes;
    use FilterSearchScope;

    protected $table = 'lead_messages';

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(LeadConversation::class, 'conversation_id');
    }

    public function createdByDealerUser(): BelongsTo
    {
        return $this->belongsTo(DealerUser::class, 'created_by_dealer_user_id');
    }

    public function createdBySystemUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'system_user_id');
    }

    public function messageable(): MorphTo
    {
        return $this->morphTo();
    }
}
