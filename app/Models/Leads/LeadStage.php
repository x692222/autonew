<?php

namespace App\Models\Leads;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Dealer\Dealer;
use App\ModelScopes\FilterSearchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadStage extends Model
{
    use HasUuidPrimaryKey;

    use SoftDeletes;
    use FilterSearchScope;

    protected $table = 'lead_stages';

    protected $guarded = [];

    protected $casts = [
        'sort_order'                    => 'integer',
        'is_terminal'                   => 'boolean',
        'is_won'                        => 'boolean',
        'is_lost'                       => 'boolean',
        'sla_minutes_to_first_response' => 'integer',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(LeadPipeline::class, 'pipeline_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'stage_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(LeadStageEvent::class, 'to_stage_id');
    }

    public function fromEvents(): HasMany
    {
        return $this->hasMany(LeadStageEvent::class, 'from_stage_id');
    }

    public function dealer(): HasOneThrough
    {
        return $this->hasOneThrough(
            Dealer::class,
            LeadPipeline::class,
            'id',
            'id',
            'pipeline_id',
            'dealer_id'
        );
    }
}
