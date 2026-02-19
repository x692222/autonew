<?php

namespace App\Models\Quotation;

use App\Models\Dealer\Dealer;
use App\Models\Invoice\Invoice;
use App\Traits\HasNotes;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasUuidPrimaryKey;
    use SoftDeletes;
    use HasNotes;

    protected $table = 'quotations';

    protected $fillable = [
        'dealer_id',
        'customer_id',
        'quote_identifier',
        'has_custom_quote_identifier',
        'quotation_date',
        'valid_for_days',
        'valid_until',
        'vat_enabled',
        'vat_percentage',
        'vat_number',
        'subtotal_before_vat',
        'vat_amount',
        'total_amount',
        'created_by_type',
        'created_by_id',
        'updated_by_type',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'has_custom_quote_identifier' => 'boolean',
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'vat_enabled' => 'boolean',
            'vat_percentage' => 'decimal:2',
            'subtotal_before_vat' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->whereNull('dealer_id');
    }

    public function scopeForDealer(Builder $query, string $dealerId): Builder
    {
        return $query->where('dealer_id', $dealerId);
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(QuotationLineItem::class, 'quotation_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'quotation_id');
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function updatedBy(): MorphTo
    {
        return $this->morphTo();
    }
}
