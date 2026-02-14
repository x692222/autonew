<?php

namespace App\Models\System;

use App\Traits\HasUuidPrimaryKey;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasUuidPrimaryKey;
}

