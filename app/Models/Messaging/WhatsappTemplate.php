<?php

namespace App\Models\Messaging;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Dealer\Dealer;
use App\Models\System\Configuration\WhatsappProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappTemplate extends Model
{
    use HasUuidPrimaryKey;

    use SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PENDING_DELETE = 'pending_delete';
    const STATUS_DELETED = 'deleted';

    const STATUS_OPTIONS = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_SUBMITTED,
        self::STATUS_PENDING_DELETE,
        self::STATUS_DELETED,
    ];

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'dealer_id',
        'provider_id',
        'provider_template_id',
        'name',
        'language',
        'category',
        'status',
        'components',
        'body',
    ];

    protected $casts = [
        'components' => 'array',
    ];

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function provider()
    {
        return $this->belongsTo(WhatsappProvider::class, 'provider_id');
    }
}
