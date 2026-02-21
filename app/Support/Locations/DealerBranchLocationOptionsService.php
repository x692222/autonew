<?php

namespace App\Support\Locations;

use App\Models\Dealer\Dealer;
use App\Support\Options\LocationOptions;

class DealerBranchLocationOptionsService
{
    public function forDealer(Dealer $dealer): array
    {
        $rows = $dealer->branches()
            ->join('location_suburbs', 'location_suburbs.id', '=', 'dealer_branches.suburb_id')
            ->join('location_cities', 'location_cities.id', '=', 'location_suburbs.city_id')
            ->join('location_states', 'location_states.id', '=', 'location_cities.state_id')
            ->join('location_countries', 'location_countries.id', '=', 'location_states.country_id')
            ->select([
                'location_countries.id as country_id',
                'location_countries.name as country_name',
                'location_states.id as state_id',
                'location_states.name as state_name',
                'location_states.country_id as state_country_id',
                'location_cities.id as city_id',
                'location_cities.name as city_name',
                'location_cities.state_id as city_state_id',
                'location_suburbs.id as suburb_id',
                'location_suburbs.name as suburb_name',
                'location_suburbs.city_id as suburb_city_id',
            ])
            ->distinct()
            ->orderBy('location_countries.name')
            ->orderBy('location_states.name')
            ->orderBy('location_cities.name')
            ->orderBy('location_suburbs.name')
            ->get();

        $countryIds = $rows->pluck('country_id')->filter()->unique()->values();
        $stateIds = $rows->pluck('state_id')->filter()->unique()->values();
        $cityIds = $rows->pluck('city_id')->filter()->unique()->values();
        $suburbIds = $rows->pluck('suburb_id')->filter()->unique()->values();

        return [
            'countries' => LocationOptions::countries(countryId: null, whereIn: $countryIds, withAll: false)->resolve(),
            'states' => LocationOptions::states(countryId: null, whereIn: $stateIds, withAll: false)->resolve(),
            'cities' => LocationOptions::cities(stateId: null, whereIn: $cityIds, withAll: false)->resolve(),
            'suburbs' => LocationOptions::suburbs(cityId: null, whereIn: $suburbIds, withAll: false)->resolve(),
        ];
    }
}
