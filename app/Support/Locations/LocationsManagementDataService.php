<?php

namespace App\Support\Locations;

use App\Models\Location\LocationCity;
use App\Models\Location\LocationCountry;
use App\Models\Location\LocationState;
use App\Models\Location\LocationSuburb;
use App\Support\Resolvers\Locations\LocationTypeResolver;
use App\Support\Options\LocationOptions;
use Illuminate\Database\Eloquent\Builder;

class LocationsManagementDataService
{
    public function tabs(): array
    {
        return [
            ['name' => LocationTypeResolver::COUNTRY, 'label' => 'Countries'],
            ['name' => LocationTypeResolver::STATE, 'label' => 'Provinces'],
            ['name' => LocationTypeResolver::CITY, 'label' => 'Cities'],
            ['name' => LocationTypeResolver::SUBURB, 'label' => 'Suburbs'],
        ];
    }

    public function columnsFor(string $tab): array
    {
        return match ($tab) {
            LocationTypeResolver::COUNTRY => [
                ['name' => 'name', 'label' => 'Name', 'sortable' => true, 'align' => 'left', 'field' => 'name', 'numeric' => false],
            ],
            LocationTypeResolver::STATE => [
                ['name' => 'name', 'label' => 'Name', 'sortable' => true, 'align' => 'left', 'field' => 'name', 'numeric' => false],
                ['name' => 'country', 'label' => 'Country', 'sortable' => false, 'align' => 'left', 'field' => 'country', 'numeric' => false],
            ],
            LocationTypeResolver::CITY => [
                ['name' => 'name', 'label' => 'Name', 'sortable' => true, 'align' => 'left', 'field' => 'name', 'numeric' => false],
                ['name' => 'state', 'label' => 'Province', 'sortable' => false, 'align' => 'left', 'field' => 'state', 'numeric' => false],
                ['name' => 'country', 'label' => 'Country', 'sortable' => false, 'align' => 'left', 'field' => 'country', 'numeric' => false],
            ],
            default => [
                ['name' => 'name', 'label' => 'Name', 'sortable' => true, 'align' => 'left', 'field' => 'name', 'numeric' => false],
                ['name' => 'city', 'label' => 'City', 'sortable' => false, 'align' => 'left', 'field' => 'city', 'numeric' => false],
                ['name' => 'state', 'label' => 'Province', 'sortable' => false, 'align' => 'left', 'field' => 'state', 'numeric' => false],
                ['name' => 'country', 'label' => 'Country', 'sortable' => false, 'align' => 'left', 'field' => 'country', 'numeric' => false],
            ],
        };
    }

    public function options(): array
    {
        return [
            'countries' => LocationOptions::countries(null, null)->resolve(),
            'states' => LocationOptions::states(null, null)->resolve(),
            'cities' => LocationOptions::cities(null, null)->resolve(),
        ];
    }

    public function editData(string $type, object $model): array
    {
        if ($type === LocationTypeResolver::COUNTRY) {
            return [
                'id' => $model->id,
                'name' => $model->name,
                'country_id' => null,
                'state_id' => null,
                'city_id' => null,
            ];
        }

        if ($type === LocationTypeResolver::STATE) {
            return [
                'id' => $model->id,
                'name' => $model->name,
                'country_id' => $model->country_id,
                'state_id' => null,
                'city_id' => null,
            ];
        }

        if ($type === LocationTypeResolver::CITY) {
            /** @var LocationCity $model */
            return [
                'id' => $model->id,
                'name' => $model->name,
                'country_id' => $model->state?->country_id,
                'state_id' => $model->state_id,
                'city_id' => null,
            ];
        }

        /** @var LocationSuburb $model */
        return [
            'id' => $model->id,
            'name' => $model->name,
            'country_id' => $model->city?->state?->country_id,
            'state_id' => $model->city?->state_id,
            'city_id' => $model->city_id,
        ];
    }

    public function indexQueryFor(string $tab, array $filters): Builder
    {
        $search = $filters['search'] ?? null;
        $countryId = $filters['country_id'] ?? null;
        $stateId = $filters['state_id'] ?? null;
        $cityId = $filters['city_id'] ?? null;

        return match ($tab) {
            LocationTypeResolver::COUNTRY => LocationCountry::query()
                ->select(['id', 'name'])
                ->when($search, fn (Builder $q) => $q->filterSearch($search, ['name'])),

            LocationTypeResolver::STATE => LocationState::query()
                ->select(['id', 'name', 'country_id'])
                ->with('country:id,name')
                ->when($countryId, fn (Builder $q) => $q->where('country_id', $countryId))
                ->when($search, fn (Builder $q) => $q->filterSearch($search, ['name'])),

            LocationTypeResolver::CITY => LocationCity::query()
                ->select(['id', 'name', 'state_id'])
                ->with('state:id,name,country_id', 'state.country:id,name')
                ->when($stateId, fn (Builder $q) => $q->where('state_id', $stateId))
                ->when($countryId, fn (Builder $q) => $q->whereHas('state', fn (Builder $sq) => $sq->where('country_id', $countryId)))
                ->when($search, fn (Builder $q) => $q->filterSearch($search, ['name'])),

            default => LocationSuburb::query()
                ->select(['id', 'name', 'city_id'])
                ->with('city:id,name,state_id', 'city.state:id,name,country_id', 'city.state.country:id,name')
                ->when($cityId, fn (Builder $q) => $q->where('city_id', $cityId))
                ->when($stateId, fn (Builder $q) => $q->whereHas('city', fn (Builder $cq) => $cq->where('state_id', $stateId)))
                ->when($countryId, fn (Builder $q) => $q->whereHas(
                    'city.state',
                    fn (Builder $sq) => $sq->where('country_id', $countryId)
                ))
                ->when($search, fn (Builder $q) => $q->filterSearch($search, ['name'])),
        };
    }
}
