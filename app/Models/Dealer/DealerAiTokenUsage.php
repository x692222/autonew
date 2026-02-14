<?php

namespace App\Models\Dealer;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Dealer\Dealer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerAiTokenUsage extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'dealer_ai_token_usages';

    protected $fillable = [
        'dealer_id',
        'consumer_type',
        'consumer_id',
        'openai_model',
        'tokens_in',
        'tokens_out',
        'total_tokens',
        'meta',
    ];

    protected $casts = [
        'tokens_in' => 'integer',
        'tokens_out' => 'integer',
        'total_tokens' => 'integer',
        'meta' => 'array',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function consumer(): ?Model
    {
        if (!$this->consumer_id || !class_exists($this->consumer_type)) {
            return null;
        }

        $class = $this->consumer_type;

        if (!is_subclass_of($class, Model::class)) {
            return null;
        }

        return $class::query()->find($this->consumer_id);
    }
}
