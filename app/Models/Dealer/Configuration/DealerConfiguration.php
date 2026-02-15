<?php

namespace App\Models\Dealer\Configuration;

use App\Enums\ConfigurationCategoryEnum;
use App\Enums\ConfigurationValueTypeEnum;
use App\Models\Dealer\Dealer;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerConfiguration extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'dealer_configurations';

    protected $fillable = [
        'dealer_id',
        'key',
        'label',
        'category',
        'type',
        'value',
        'description',
        'backoffice_only',
    ];

    protected function casts(): array
    {
        return [
            'category' => ConfigurationCategoryEnum::class,
            'type' => ConfigurationValueTypeEnum::class,
            'backoffice_only' => 'boolean',
        ];
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }
}
