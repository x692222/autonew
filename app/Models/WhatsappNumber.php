<?php

namespace App\Models;

use App\Models\Dealer\Dealer;
use App\Models\System\Configuration\WhatsappProvider;
use App\Traits\HasActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappNumber extends Model
{
    use HasActivityTrait;
    use SoftDeletes;

    public const TYPE_SYSTEM = 'system';
    public const TYPE_DEALER = 'dealer';
    public const TYPE_UNASSIGNED = 'unassigned';

    public const TYPE_OPTIONS = [
        self::TYPE_SYSTEM,
        self::TYPE_DEALER,
        self::TYPE_UNASSIGNED,
    ];

    protected $table = 'whatsapp_numbers';

    protected $fillable = [
        'type',
        'provider_id',
        'dealer_id',
        'msisdn',
        'configuration',
    ];

    protected $hidden = [
        //
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array', // json object -> associative array
        ];
    }

    // relationships

    public function provider(): BelongsTo
    {
        return $this->belongsTo(WhatsappProvider::class, 'provider_id');
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    // functions

    /**
     * Get the dealer's WhatsApp number (excluding soft-deleted).
     *
     * Returns:
     * - WhatsappNumber instance if assigned
     * - null if not assigned
     */
    public static function forDealerId(int $dealerId): ?WhatsappNumber
    {
        return WhatsappNumber::query()
            ->where('dealer_id', $dealerId)
            ->where('type', WhatsappNumber::TYPE_DEALER) // extra safety
            ->first();
    }

    /**
     * check if dealer has a WhatsApp number.
     */
    public static function dealerHasNumber(int $dealerId): bool
    {
        return self::forDealerId($dealerId) !== null;
    }
}
