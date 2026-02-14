<?php

namespace App\Models\System\Configuration;

use App\Models\Messaging\WhatsappTemplate;
use App\Models\WhatsappNumber;
use App\Traits\HasActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappProvider extends Model
{
    use HasActivityTrait;

    protected $table = 'system_whatsapp_providers';

    protected $fillable = [
        'identifier',
        'config_fields',
    ];

    protected $hidden = [
        //
    ];

    protected function casts(): array
    {
        return [
            'config_fields' => 'array', // json object -> associative array
        ];
    }

    // relationships

    public function whatsappNumbers(): HasMany
    {
        return $this->hasMany(WhatsappNumber::class, 'provider_id');
    }

    public function whatsappTemplates()
    {
        return $this->hasMany(WhatsappTemplate::class, 'provider_id');
    }
}
