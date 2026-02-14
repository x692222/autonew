<?php

namespace App\ModelScopes;

use Illuminate\Database\Eloquent\Builder;

trait FilterLocationsScope
{

    public function scopeFilterLocations(Builder $query, ?int $countryId, ?int $stateId, ?int $cityId, ?int $suburbId): Builder
    {
        return $query
            ->when($suburbId, fn (Builder $q) => $q->where('suburb_id', $suburbId))

            ->when(!$suburbId && $cityId, function (Builder $q) use ($cityId) {
                $q->whereHas('suburb', fn (Builder $sq) => $sq->where('city_id', $cityId));
            })

            ->when(!$suburbId && !$cityId && $stateId, function (Builder $q) use ($stateId) {
                $q->whereHas('suburb.city', fn (Builder $cq) => $cq->where('state_id', $stateId));
            })

            ->when(!$suburbId && !$cityId && !$stateId && $countryId, function (Builder $q) use ($countryId) {
                $q->whereHas('suburb.city.state', fn (Builder $sq) => $sq->where('country_id', $countryId));
            });
    }

}
