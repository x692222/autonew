<?php

namespace App\Models\Leads;

use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerUser;
use App\ModelScopes\FilterSearchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class LeadStageEvent extends Model
{

    use FilterSearchScope;

    protected $table = 'lead_stage_events';

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(LeadStage::class, 'from_stage_id');
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(LeadStage::class, 'to_stage_id');
    }

    public function changedByDealerUser(): BelongsTo
    {
        return $this->belongsTo(DealerUser::class, 'changed_by_dealer_user_id');
    }

    public function dealer(): HasOneThrough
    {
        return $this->hasOneThrough(
            Dealer::class,
            Lead::class,
            'id',
            'id',
            'lead_id',
            'dealer_id'
        );
    }
}
