<?php

namespace App\Models\Invoice;

use App\Enums\InvoiceLineItemSectionEnum;
use App\Models\Dealer\Dealer;
use App\Models\Stock\Stock;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLineItem extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'invoice_line_items';

    protected $fillable = [
        'invoice_id',
        'dealer_id',
        'stock_id',
        'section',
        'sku',
        'description',
        'amount',
        'qty',
        'total',
        'is_vat_exempt',
    ];

    protected function casts(): array
    {
        return [
            'section' => InvoiceLineItemSectionEnum::class,
            'amount' => 'decimal:2',
            'qty' => 'decimal:2',
            'total' => 'decimal:2',
            'is_vat_exempt' => 'boolean',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
}

