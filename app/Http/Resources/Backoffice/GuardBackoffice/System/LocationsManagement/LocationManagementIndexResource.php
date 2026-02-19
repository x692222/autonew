<?php

namespace App\Http\Resources\Backoffice\GuardBackoffice\System\LocationsManagement;
use App\Models\Location\LocationCity;
use App\Models\Location\LocationCountry;
use App\Models\Location\LocationState;
use App\Models\Location\LocationSuburb;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationManagementIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $actor = $request->user('backoffice');
        $resource = $this->resource;

        if ($resource instanceof LocationCountry) {
            return [
                'id' => $resource->id,
                'name' => $resource->name,
                'country' => null,
                'state' => null,
                'city' => null,
                'can' => [
                    'update' => $actor?->can('update', $resource) ?? false,
                    'delete' => $actor?->can('delete', $resource) ?? false,
                ],
            ];
        }

        if ($resource instanceof LocationState) {
            return [
                'id' => $resource->id,
                'name' => $resource->name,
                'country' => $resource->country?->name,
                'country_id' => $resource->country_id,
                'state' => null,
                'city' => null,
                'can' => [
                    'update' => $actor?->can('update', $resource) ?? false,
                    'delete' => $actor?->can('delete', $resource) ?? false,
                ],
            ];
        }

        if ($resource instanceof LocationCity) {
            return [
                'id' => $resource->id,
                'name' => $resource->name,
                'country' => $resource->state?->country?->name,
                'country_id' => $resource->state?->country_id,
                'state' => $resource->state?->name,
                'state_id' => $resource->state_id,
                'city' => null,
                'can' => [
                    'update' => $actor?->can('update', $resource) ?? false,
                    'delete' => $actor?->can('delete', $resource) ?? false,
                ],
            ];
        }

        if ($resource instanceof LocationSuburb) {
            return [
                'id' => $resource->id,
                'name' => $resource->name,
                'country' => $resource->city?->state?->country?->name,
                'country_id' => $resource->city?->state?->country_id,
                'state' => $resource->city?->state?->name,
                'state_id' => $resource->city?->state_id,
                'city' => $resource->city?->name,
                'city_id' => $resource->city_id,
                'can' => [
                    'update' => $actor?->can('update', $resource) ?? false,
                    'delete' => $actor?->can('delete', $resource) ?? false,
                ],
            ];
        }

        return [];
    }
}
