<?php

namespace App\Models\System;

use App\Traits\HasUuidPrimaryKey;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasUuidPrimaryKey;
}

