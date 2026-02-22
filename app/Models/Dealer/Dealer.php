<?php

namespace App\Models\Dealer;

use App\Traits\HasUuidPrimaryKey;

use App\Models\Ai\AiConversation;
use App\Models\Dealer\Configuration\DealerConfiguration;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadConversation;
use App\Models\Leads\LeadMessage;
use App\Models\Leads\LeadPipeline;
use App\Models\Leads\LeadStage;
use App\Models\LineItem\StoredLineItem;
use App\Models\Messaging\WhatsappTemplate;
use App\Models\Quotation\Customer;
use App\Models\Quotation\Quotation;
use App\Models\Quotation\QuotationLineItem;
use App\Models\Invoice\Invoice;
use App\Models\Invoice\InvoiceLineItem;
use App\Models\Stock\Stock;
use App\Models\WhatsappNumber;
use App\ModelScopes\FilterSearchScope;
use App\ModelScopes\FilterActiveStatusScope;
use App\Traits\HasActivityTrait;
use App\Traits\HasNotes;
use App\Traits\SluggableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dealer extends Model
{
    use HasUuidPrimaryKey;

    use SoftDeletes;
    use SluggableTrait;
    use HasActivityTrait;
    use FilterSearchScope;
    use FilterActiveStatusScope;
    use HasNotes;

    protected $table = 'dealers';

    protected $fillable = [
        'is_active',
        'name',
        'context',
    ];

    protected $hidden = [

    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // observers

    // scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    // relationships

    public function branches(): HasMany
    {
        return $this->hasMany(DealerBranch::class, 'dealer_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(DealerUser::class, 'dealer_id');
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class, 'dealer_id');
    }

    public function aiTokenUsages(): HasMany
    {
        return $this->hasMany(DealerAiTokenUsage::class, 'dealer_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'dealer_id');
    }

    public function leadMessages(): HasMany
    {
        return $this->hasMany(LeadMessage::class, 'dealer_id');
    }

    public function leadConversations(): HasMany
    {
        return $this->hasMany(LeadConversation::class, 'dealer_id');
    }

    public function buckets(): HasManyThrough
    {
        return $this->hasManyThrough(
            DealerUserBucket::class,
            DealerUser::class,
            'dealer_id',
            'user_id',
            'id',
            'id'
        );
    }


    public function stockItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            Stock::class,
            DealerBranch::class,
            'dealer_id',
            'branch_id',
            'id',
            'id'
        );
    }

    public function pipelines(): HasMany
    {
        return $this->hasMany(LeadPipeline::class, 'dealer_id');
    }

    public function stages(): HasManyThrough
    {
        return $this->hasManyThrough(
            LeadStage::class,
            LeadPipeline::class,
            'dealer_id',
            'pipeline_id',
            'id',
            'id'
        );
    }

    public function whatsappNumbers(): HasMany
    {
        return $this->hasMany(WhatsappNumber::class, 'dealer_id');
    }

    public function whatsappTemplates(): HasMany
    {
        return $this->hasMany(WhatsappTemplate::class, 'dealer_id');
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(DealerConfiguration::class, 'dealer_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'dealer_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'dealer_id');
    }

    public function quotationLineItems(): HasMany
    {
        return $this->hasMany(QuotationLineItem::class, 'dealer_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'dealer_id');
    }

    public function invoiceLineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class, 'dealer_id');
    }

    public function storedLineItems(): HasMany
    {
        return $this->hasMany(StoredLineItem::class, 'dealer_id');
    }


    // $activeVehicles = $dealer->stockItems()->active()->typeVehicle()->get();
}
