<?php

namespace App\Models\Leads\Channels;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Leads\LeadConversation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class EmailThread extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'email_threads';

    protected $guarded = [];

    protected $casts = [
        'cc' => 'array',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function conversation(): MorphOne
    {
        return $this->morphOne(LeadConversation::class, 'channelable');
    }
}
