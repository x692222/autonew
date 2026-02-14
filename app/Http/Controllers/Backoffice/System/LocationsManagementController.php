<?php

namespace App\Http\Controllers\Backoffice\System;

use App\Actions\System\Locations\CreateLocationAction;
use App\Actions\System\Locations\DeleteLocationAction;
use App\Actions\System\Locations\UpdateLocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\System\LocationsManagement\CreateLocationsManagementRequest;
use App\Http\Requests\Backoffice\System\LocationsManagement\DestroyLocationsManagementRequest;
use App\Http\Requests\Backoffice\System\LocationsManagement\EditLocationsManagementRequest;
use App\Http\Requests\Backoffice\System\LocationsManagement\IndexLocationsManagementRequest;
use App\Http\Requests\Backoffice\System\LocationsManagement\StoreLocationsManagementRequest;
use App\Http\Requests\Backoffice\System\LocationsManagement\UpdateLocationsManagementRequest;
use App\Http\Resources\Backoffice\System\LocationsManagement\LocationManagementIndexResource;
use App\Models\Location\LocationCity;
use App\Models\Location\LocationCountry;
use App\Models\Location\LocationState;
use App\Models\Location\LocationSuburb;
use App\Support\Locations\LocationTypeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LocationsManagementController extends Controller
{
    private string $publicTitle = 'Location Management';

    public function index(IndexLocationsManagementRequest $request): Response
    {
        $filters = $request->validated();
        $tab = $filters['tab'] ?? LocationTypeResolver::COUNTRY;

        $query = $this->indexQueryFor($tab, $filters);
        $descending = filter_var($filters['descending'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';
        $query->orderBy('name', $descending);

        $records = $query
            ->paginate((int) ($filters['rowsPerPage'] ?? 25))
            ->appends($filters);

        $records->setCollection(
            $records->getCollection()->map(
                fn ($location) => (new LocationManagementIndexResource($location))->toArray($request)
            )
        );

        return Inertia::render('System/LocationsManagement/Index', [
            'publicTitle' => $this->publicTitle,
            'tab' => $tab,
            'filters' => $filters,
            'columns' => $this->columnsFor($tab),
            'records' => $records,
            'options' => $this->options(),
            'tabs' => $this->tabs(),
        ]);
    }

    public function create(CreateLocationsManagementRequest $request, string $type): Response
    {
        return Inertia::render('System/LocationsManagement/Create', [
            'publicTitle' => $this->publicTitle,
            'type' => $type,
            'typeLabel' => LocationTypeResolver::singularLabel($type),
            'options' => $this->options(),
        ]);
    }

    public function store(
        StoreLocationsManagementRequest $request,
        string $type,
        CreateLocationAction $action
    ): RedirectResponse {
        $action->execute($type, $request->validated());

        return redirect()
            ->route('backoffice.system.locations-management.index', ['tab' => $type])
            ->with('success', LocationTypeResolver::singularLabel($type).' created.');
    }

    public function edit(EditLocationsManagementRequest $request, string $type, string $location): Response
    {
        $model = LocationTypeResolver::findOrFail($type, $location);

        if ($model instanceof LocationCity) {
            $model->load('state:id,country_id');
        }

        if ($model instanceof LocationSuburb) {
            $model->load('city:id,state_id', 'city.state:id,country_id');
        }

        return Inertia::render('System/LocationsManagement/Edit', [
            'publicTitle' => $this->publicTitle,
            'type' => $type,
            'typeLabel' => LocationTypeResolver::singularLabel($type),
            'options' => $this->options(),
            'data' => $this->editData($type, $model),
        ]);
    }

    public function update(
        UpdateLocationsManagementRequest $request,
        string $type,
        string $location,
        UpdateLocationAction $action
    ): RedirectResponse {
        $model = LocationTypeResolver::findOrFail($type, $location);
        $action->execute($type, $model, $request->validated());

        return redirect()
            ->route('backoffice.system.locations-management.index', ['tab' => $type])
            ->with('success', LocationTypeResolver::singularLabel($type).' updated.');
    }

    public function destroy(
        DestroyLocationsManagementRequest $request,
        string $type,
        string $location,
        DeleteLocationAction $action
    ): RedirectResponse {
        $model = LocationTypeResolver::findOrFail($type, $location);

        // @todo Revisit destroy behavior and potential soft-delete restoration UX.
        try {
            $action->execute($model);
        } catch (QueryException) {
            return back()->with('error', 'Unable to delete this location because it is in use.');
        }

        return back()->with('success', LocationTypeResolver::singularLabel($type).' deleted.');
    }

    private function tabs(): array
    {
        return [
            ['name' => LocationTypeResolver::COUNTRY, 'label' => 'Countries'],
            ['name' => LocationTypeResolver::STATE, 'label' => 'States'],
            ['name' => LocationTypeResolver::CITY, 'label' => 'Cities'],
            ['name' => LocationTypeResolver::SUBURB, 'label' => 'Suburbs'],
        ];
    }

    private function columnsFor(string $tab): array
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
                ['name' => 'state', 'label' => 'State', 'sortable' => false, 'align' => 'left', 'field' => 'state', 'numeric' => false],
                ['name' => 'country', 'label' => 'Country', 'sortable' => false, 'align' => 'left', 'field' => 'country', 'numeric' => false],
            ],
            default => [
                ['name' => 'name', 'label' => 'Name', 'sortable' => true, 'align' => 'left', 'field' => 'name', 'numeric' => false],
                ['name' => 'city', 'label' => 'City', 'sortable' => false, 'align' => 'left', 'field' => 'city', 'numeric' => false],
                ['name' => 'state', 'label' => 'State', 'sortable' => false, 'align' => 'left', 'field' => 'state', 'numeric' => false],
                ['name' => 'country', 'label' => 'Country', 'sortable' => false, 'align' => 'left', 'field' => 'country', 'numeric' => false],
            ],
        };
    }

    private function options(): array
    {
        return [
            'countries' => LocationCountry::query()
                ->select(['id as value', 'name as label'])
                ->orderBy('name')
                ->get()
                ->toArray(),
            'states' => LocationState::query()
                ->select(['id as value', 'name as label', 'country_id'])
                ->orderBy('name')
                ->get()
                ->toArray(),
            'cities' => LocationCity::query()
                ->select(['id as value', 'name as label', 'state_id'])
                ->orderBy('name')
                ->get()
                ->toArray(),
        ];
    }

    private function editData(string $type, object $model): array
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

    private function indexQueryFor(string $tab, array $filters): Builder
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
