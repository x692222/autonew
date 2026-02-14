<?php

namespace App\ModelScopes;

use Illuminate\Database\Eloquent\Builder;

trait FilterActiveStatusScope
{

    public function scopeFilterActiveStatus(Builder $query, ?string $status): Builder
    {
        $status = trim((string)$status);
        if (empty($status)) {
            return $query;
        }

        if ($status === 'active') $query->where('dealers.is_active', true);
        if ($status === 'inactive') $query->where('dealers.is_active', false);

        return $query;
    }

}
