<?php

namespace App\Models\Leads\Channels;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Leads\LeadMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class EmailMessage extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'email_messages';

    protected $guarded = [];

    protected $casts = [
        'to_email' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'references' => 'array',
        'attachments' => 'array',
        'raw_headers' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function message(): MorphOne
    {
        return $this->morphOne(LeadMessage::class, 'messageable');
    }
}
