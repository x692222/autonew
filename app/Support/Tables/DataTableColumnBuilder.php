<?php

namespace App\Support\Tables;

use Illuminate\Support\Str;

final class DataTableColumnBuilder
{
    public static function make(
        array $keys,
        array $sortableKeys = [],
        array $numericKeys = [],
        array $alignOverrides = [],
        bool $allSortable = false,
        bool $numericCountSuffix = false,
        string $countSuffix = '_count',
        string $defaultAlign = 'left',
    ): array {
        return collect($keys)
            ->map(function (string $key) use (
                $sortableKeys,
                $numericKeys,
                $alignOverrides,
                $allSortable,
                $numericCountSuffix,
                $countSuffix,
                $defaultAlign
            ): array {
                $numeric = in_array($key, $numericKeys, true)
                    || ($numericCountSuffix && Str::endsWith($key, $countSuffix));

                return [
                    'name' => $key,
                    'label' => Str::headline($key),
                    'sortable' => $allSortable || in_array($key, $sortableKeys, true),
                    'align' => $alignOverrides[$key] ?? ($numeric ? 'right' : $defaultAlign),
                    'field' => $key,
                    'numeric' => $numeric,
                ];
            })
            ->values()
            ->all();
    }
}
