<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


function isConsole()
{
    return app()->runningInConsole();
}

function isSessionBackoffice(): bool
{
    return auth()->guard('backoffice')->check() ?? false;
}

function isDealerSession(): bool
{
    return auth()->guard('dealer')->check() ?? false;
}

function tableHasColumn(string $table, string $column): bool
{
    return cache()->rememberForever("stock:table_has_column:{$table}:{$column}v1", function() use ($table, $column) {
        return Schema::hasColumn($table, $column);
    });
}

/**
 * Accepts "-18.7583, 16.3838" (commas/spaces ok) and returns [lat,lng] floats or [null,null].
 */
function parseCoordinates(?string $coordinates): array
{
    if (!$coordinates) {
        return [null, null];
    }

    $raw = trim($coordinates);
    if ($raw === '') {
        return [null, null];
    }

    $raw = str_replace([';', "\t", "\n"], ',', $raw);

    $parts = array_values(array_filter(array_map('trim', explode(',', $raw)), fn($p) => $p !== ''));

    if (count($parts) !== 2) {
        return [null, null];
    }

    $lat = is_numeric($parts[0]) ? (float)$parts[0] : null;
    $lng = is_numeric($parts[1]) ? (float)$parts[1] : null;

    if ($lat === null || $lng === null) {
        return [null, null];
    }
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        return [null, null];
    }

    return [$lat, $lng];
}

function iterableToCacheString(iterable|null $iterable)
{
    return collect($iterable)->filter(fn($v) => filled($v))->map(fn($v, $k) => "{$k}:{$v}")->implode('|');
}
