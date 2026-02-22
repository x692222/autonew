<?php

namespace App\Models\LineItem;

use App\Models\Dealer\Dealer;
use App\Traits\HasUuidPrimaryKey;
use LogicException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoredLineItem extends Model
{
    use HasUuidPrimaryKey;

    public const SYSTEM_SCOPE_KEY = '__system__';

    protected $table = 'stored_line_items';

    protected $fillable = [
        'dealer_id',
        'scope_key',
        'section',
        'sku',
        'description',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public static function scopeKeyFor(?string $dealerId): string
    {
        return $dealerId ?: self::SYSTEM_SCOPE_KEY;
    }

    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new LogicException('Stored line items are immutable and cannot be deleted.');
        });
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }
}
