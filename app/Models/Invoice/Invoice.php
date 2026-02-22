<?php

namespace App\Models\Invoice;

use App\Models\Dealer\Dealer;
use App\Models\Payments\Payment;
use App\Models\Quotation\Customer;
use App\Models\Quotation\Quotation;
use App\Traits\HasNotes;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasUuidPrimaryKey;
    use SoftDeletes;
    use HasNotes;

    protected $table = 'invoices';

    protected $fillable = [
        'dealer_id',
        'quotation_id',
        'customer_id',
        'invoice_identifier',
        'has_custom_invoice_identifier',
        'invoice_date',
        'payable_by',
        'purchase_order_number',
        'payment_terms',
        'is_fully_paid',
        'vat_enabled',
        'vat_percentage',
        'vat_number',
        'subtotal_before_vat',
        'vat_amount',
        'created_by_type',
        'created_by_id',
        'updated_by_type',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'has_custom_invoice_identifier' => 'boolean',
            'invoice_date' => 'date',
            'payable_by' => 'date',
            'vat_enabled' => 'boolean',
            'vat_percentage' => 'decimal:2',
            'subtotal_before_vat' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'is_fully_paid' => 'boolean',
        ];
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->whereNull($query->qualifyColumn('dealer_id'));
    }

    public function scopeForDealer(Builder $query, string $dealerId): Builder
    {
        return $query->where($query->qualifyColumn('dealer_id'), $dealerId);
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
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
