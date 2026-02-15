<?php

namespace App\Models\System\Configuration;

use App\Enums\ConfigurationCategoryEnum;
use App\Enums\ConfigurationValueTypeEnum;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'system_configurations';

    protected $fillable = [
        'key',
        'label',
        'category',
        'type',
        'value',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'category' => ConfigurationCategoryEnum::class,
            'type' => ConfigurationValueTypeEnum::class,
        ];
    }
}
