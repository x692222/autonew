<?php

namespace App\Support\Lookups;

use App\Models\Location\LocationCity;
use App\Models\Location\LocationState;
use App\Models\Location\LocationSuburb;

class LocationLookup
{

    public static function filterLocationHierarchyIds(?array $ids = null): array
    {
        $key = collect($ids)->filter(fn ($v) => filled($v))->map(fn ($v, $k) => "{$k}:{$v}")->implode('|');

        return cache()->rememberForever("locations:full:{$key}:v1", function() use ($ids) {
            $suburbId  = isset($ids['suburb_id']) ? (int)$ids['suburb_id'] : null;
            $cityId    = isset($ids['city_id']) ? (int)$ids['city_id'] : null;
            $stateId   = isset($ids['state_id']) ? (int)$ids['state_id'] : null;
            $countryId = isset($ids['country_id']) ? (int)$ids['country_id'] : null;

            // From suburb -> city/state/country
            if ($suburbId && (!$cityId || !$stateId || !$countryId)) {
                $row = LocationSuburb::query()
                    ->select([
                        'location_suburbs.city_id as city_id',
                        'location_cities.state_id as state_id',
                        'location_states.country_id as country_id',
                    ])
                    ->whereKey($suburbId)
                    ->whereNull('location_suburbs.deleted_at')
                    ->join('location_cities', 'location_cities.id', '=', 'location_suburbs.city_id')
                    ->join('location_states', 'location_states.id', '=', 'location_cities.state_id')
                    ->join('location_countries', 'location_countries.id', '=', 'location_states.country_id')
                    ->whereNull('location_cities.deleted_at')
                    ->whereNull('location_states.deleted_at')
                    ->whereNull('location_countries.deleted_at')
                    ->first();

                if ($row) {
                    $cityId    = $cityId ?: (int)$row->city_id;
                    $stateId   = $stateId ?: (int)$row->state_id;
                    $countryId = $countryId ?: (int)$row->country_id;
                }
            }

            // From city -> state/country
            if ($cityId && (!$stateId || !$countryId)) {
                $row = LocationCity::query()
                    ->select([
                        'location_cities.state_id as state_id',
                        'location_states.country_id as country_id',
                    ])
                    ->whereKey($cityId)
                    ->whereNull('location_cities.deleted_at')
                    ->join('location_states', 'location_states.id', '=', 'location_cities.state_id')
                    ->whereNull('location_states.deleted_at')
                    ->first();

                if ($row) {
                    $stateId   = $stateId ?: (int)$row->state_id;
                    $countryId = $countryId ?: (int)$row->country_id;
                }
            }

            // From state -> country
            if ($stateId && !$countryId) {
                $row = LocationState::query()
                    ->select(['country_id'])
                    ->whereKey($stateId)
                    ->whereNull('location_states.deleted_at')
                    ->first();

                if ($row) {
                    $countryId = (int)$row->country_id;
                }
            }

            return [
                'suburb_id'  => $suburbId ?: null,
                'city_id'    => $cityId ?: null,
                'state_id'   => $stateId ?: null,
                'country_id' => $countryId ?: null,
            ];
        });

    }

}
