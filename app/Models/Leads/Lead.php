<?php

namespace App\Models\Leads;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerUser;
use App\Models\Stock\Stock;
use App\ModelScopes\FilterSearchScope;
use App\Traits\HasNotes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasUuidPrimaryKey;

    use SoftDeletes;
    use HasNotes;
    use FilterSearchScope;

    const LEAD_CHANNEL_WHATSAPP = 'whatsapp';
    const LEAD_CHANNEL_EMAIL = 'email';
    const LEAD_CHANNEL_UNKNOWN = 'unknown';
    const LEAD_CHANNELS = [
        self::LEAD_CHANNEL_WHATSAPP,
        self::LEAD_CHANNEL_EMAIL,
        self::LEAD_CHANNEL_UNKNOWN,
    ];

    const LEAD_SOURCE_WHATSAPP = 'whatsapp';
    const LEAD_SOURCE_EMAIL = 'email';
    const LEAD_SOURCE_WEBSITE = 'website';
    const LEAD_SOURCE_WALK_IN = 'walk_in';
    const LEAD_SOURCE_CALL = 'call';
    const LEAD_SOURCES = [
        self::LEAD_SOURCE_WHATSAPP,
        self::LEAD_SOURCE_EMAIL,
        self::LEAD_SOURCE_WEBSITE,
        self::LEAD_SOURCE_WALK_IN,
        self::LEAD_SOURCE_CALL,
    ];

    const LEAD_STATUS_OPEN = 'open';
    const LEAD_STATUS_CLOSED = 'closed';
    const LEAD_STATUSES = [
        self::LEAD_STATUS_OPEN,
        self::LEAD_STATUS_CLOSED,
    ];

    protected $table = 'leads';

    protected $guarded = [];

    protected $casts = [
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(DealerBranch::class, 'branch_id');
    }

    public function assignedToDealerUser(): BelongsTo
    {
        return $this->belongsTo(DealerUser::class, 'assigned_to_dealer_user_id');
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(LeadPipeline::class, 'pipeline_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(LeadStage::class, 'stage_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(LeadConversation::class, 'lead_id');
    }

    public function stageEvents(): HasMany
    {
        return $this->hasMany(LeadStageEvent::class, 'lead_id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(LeadGoal::class, 'lead_id');
    }

    public function stockItems()
    {
        return $this->belongsToMany(Stock::class, 'lead_stock')->withTimestamps();
    }

}
