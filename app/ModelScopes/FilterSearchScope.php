<?php

namespace App\ModelScopes;

use Illuminate\Database\Eloquent\Builder;

trait FilterSearchScope
{
    public function scopeFilterSearch(Builder $query, ?string $search, array $columns): Builder
    {
        $search = trim((string) $search);
        if ($search === '' || empty($columns)) {
            return $query;
        }

        $terms = array_values(array_filter(array_map('trim', explode(',', $search))));
        if (empty($terms)) {
            return $query;
        }

        return $query->where(function (Builder $outer) use ($terms, $columns) {
            foreach ($terms as $term) {
                $outer->where(function (Builder $perTerm) use ($term, $columns) {
                    foreach ($columns as $i => $col) {
                        $i === 0
                            ? $perTerm->where($col, 'like', "%{$term}%")
                            : $perTerm->orWhere($col, 'like', "%{$term}%");
                    }
                });
            }
        });
    }
}
