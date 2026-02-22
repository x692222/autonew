<?php

namespace App\Models\Security;

use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    use HasUuidPrimaryKey;

    protected $table = 'blocked_ips';

    protected $fillable = [
        'ip_address',
        'guard_name',
        'failed_attempts',
        'blocked_at',
        'country',
    ];

    protected function casts(): array
    {
        return [
            'failed_attempts' => 'integer',
            'blocked_at' => 'datetime',
        ];
    }
}
