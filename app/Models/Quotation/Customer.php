<?php

namespace App\Models\Quotation;

use App\Enums\QuotationCustomerTypeEnum;
use App\Models\Dealer\Dealer;
use App\Models\Invoice\Invoice;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasUuidPrimaryKey;
    use SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'dealer_id',
        'type',
        'title',
        'firstname',
        'lastname',
        'id_number',
        'email',
        'contact_number',
        'address',
        'vat_number',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuotationCustomerTypeEnum::class,
        ];
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'customer_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }
}
