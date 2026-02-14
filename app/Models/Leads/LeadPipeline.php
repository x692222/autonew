<?php

namespace App\Models\Leads;

use App\Models\Dealer\Dealer;
use App\ModelScopes\FilterSearchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadPipeline extends Model
{
    use SoftDeletes;
    use FilterSearchScope;

    protected $table = 'lead_pipelines';

    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'pipeline_id');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(LeadStage::class, 'pipeline_id');
    }

}
