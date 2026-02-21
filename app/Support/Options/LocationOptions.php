<?php

namespace App\Support\Options;

use App\Http\Resources\KeyValueOptions\Location\CityCollection;
use App\Http\Resources\KeyValueOptions\Location\CountryCollection;
use App\Http\Resources\KeyValueOptions\Location\StateCollection;
use App\Http\Resources\KeyValueOptions\Location\SuburbCollection;
use App\Models\Location\LocationCity;
use App\Models\Location\LocationCountry;
use App\Models\Location\LocationState;
use App\Models\Location\LocationSuburb;

final class LocationOptions extends AbstractOptions
{
    private const CACHE_VERSION_KEY = 'locations:options:cache_version';

    public static function bumpCacheVersion(): void
    {
        $current = (int) cache()->get(self::CACHE_VERSION_KEY, 1);
        cache()->forever(self::CACHE_VERSION_KEY, $current + 1);
    }

    private static function cacheVersion(): int
    {
        return (int) cache()->get(self::CACHE_VERSION_KEY, 1);
    }

    public static function cities(?string $stateId, iterable|null $whereIn, bool $withAll = false): CityCollection
    {
        $version = self::cacheVersion();
        $key = iterableToCacheString($whereIn);
        $items = cache()->rememberForever("locations:cities:{$stateId}:{$key}:{$withAll}:v{$version}", function() use ($stateId, $whereIn) {
            return LocationCity::select(['id as value', 'name as label', 'state_id'])
                ->when($stateId, fn($q) => $q->where('state_id', $stateId))
                ->when($whereIn, fn($q) => $q->whereIn('id', $whereIn))
                ->orderBy('name')
                ->get()
                ->map(fn($m) => [
                    'label'    => $m->label,
                    'value'    => $m->value,
                    'state_id' => $m->state_id,
                ])
                ->all();
        });

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new CityCollection($options);
    }

    public static function countries(?string $countryId, iterable|null $whereIn, bool $withAll = false): CountryCollection
    {
        $version = self::cacheVersion();
        $key = iterableToCacheString($whereIn);
        $items = cache()->rememberForever("locations:countries:{$countryId}:{$key}{$withAll}:v{$version}", function() use ($countryId, $whereIn) {
            return LocationCountry::select(['id as value', 'name as label'])
                ->when($countryId, fn($q) => $q->where('id', $countryId))
                ->when($whereIn, fn($q) => $q->whereIn('id', $whereIn))
                ->orderBy('name')
                ->get()
                ->map(fn($m) => [
                    'label' => $m->label,
                    'value' => $m->value,
                ])
                ->all();
        });

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new CountryCollection($options);
    }

    public static function states(?string $countryId, iterable|null $whereIn, bool $withAll = false): StateCollection
    {
        $version = self::cacheVersion();
        $key = iterableToCacheString($whereIn);
        $items = cache()->rememberForever("locations:states:{$countryId}:{$key}:{$withAll}:v{$version}", function() use ($countryId, $whereIn) {
            return LocationState::select(['id as value', 'name as label', 'country_id'])
                ->when($countryId, fn($q) => $q->where('country_id', $countryId))
                ->when($whereIn, fn($q) => $q->whereIn('id', $whereIn))
                ->orderBy('name')
                ->get()
                ->map(fn($m) => [
                    'label'      => $m->label,
                    'value'      => $m->value,
                    'country_id' => $m->country_id,
                ])
                ->all();
        });

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new StateCollection($options);
    }

    public static function suburbs(?string $cityId, iterable|null $whereIn, bool $withAll = false): SuburbCollection
    {
        $version = self::cacheVersion();
        $key = iterableToCacheString($whereIn);
        $items = cache()->rememberForever("locations:suburbs:{$cityId}:{$key}:{$withAll}:v{$version}", function() use ($cityId, $whereIn) {
            return LocationSuburb::select(['id as value', 'name as label', 'city_id'])
                ->when($cityId, fn($q) => $q->where('city_id', $cityId))
                ->when($whereIn, fn($q) => $q->whereIn('id', $whereIn))
                ->orderBy('name')
                ->get()
                ->map(fn($m) => [
                    'label'   => $m->label,
                    'value'   => $m->value,
                    'city_id' => $m->city_id,
                ])
                ->all();
        });

        $options = collect($items);

        if ($withAll) {
            $options = self::prependAll($options);
        }

        return new SuburbCollection($options);
    }

}
