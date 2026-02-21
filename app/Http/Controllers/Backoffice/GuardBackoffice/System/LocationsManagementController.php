<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\System;
use App\Actions\Backoffice\GuardBackoffice\System\Locations\CreateLocationAction;
use App\Actions\Backoffice\GuardBackoffice\System\Locations\DeleteLocationAction;
use App\Actions\Backoffice\GuardBackoffice\System\Locations\UpdateLocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\System\LocationsManagement\CreateLocationsManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\LocationsManagement\DestroyLocationsManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\LocationsManagement\EditLocationsManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\LocationsManagement\IndexLocationsManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\LocationsManagement\StoreLocationsManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\LocationsManagement\UpdateLocationsManagementRequest;
use App\Http\Resources\Backoffice\GuardBackoffice\System\LocationsManagement\LocationManagementIndexResource;
use App\Models\Location\LocationCity;
use App\Models\Location\LocationSuburb;
use App\Support\Locations\LocationsManagementDataService;
use App\Support\Resolvers\Locations\LocationTypeResolver;
use Illuminate\Database\QueryException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LocationsManagementController extends Controller
{
    private string $publicTitle = 'Location Management';

    public function __construct(private readonly LocationsManagementDataService $dataService)
    {
    }

    public function index(IndexLocationsManagementRequest $request): Response
    {
        $filters = $request->validated();
        $tab = $filters['tab'] ?? LocationTypeResolver::COUNTRY;

        $query = $this->dataService->indexQueryFor($tab, $filters);
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

        return Inertia::render('GuardBackoffice/System/LocationsManagement/Index', [
            'publicTitle' => $this->publicTitle,
            'tab' => $tab,
            'filters' => $filters,
            'columns' => $this->dataService->columnsFor($tab),
            'records' => $records,
            'options' => $this->dataService->options(),
            'tabs' => $this->dataService->tabs(),
        ]);
    }

    public function create(CreateLocationsManagementRequest $request, string $type): Response
    {
        return Inertia::render('GuardBackoffice/System/LocationsManagement/Create', [
            'publicTitle' => $this->publicTitle,
            'type' => $type,
            'typeLabel' => LocationTypeResolver::singularLabel($type),
            'options' => $this->dataService->options(),
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

        return Inertia::render('GuardBackoffice/System/LocationsManagement/Edit', [
            'publicTitle' => $this->publicTitle,
            'type' => $type,
            'typeLabel' => LocationTypeResolver::singularLabel($type),
            'options' => $this->dataService->options(),
            'data' => $this->dataService->editData($type, $model),
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
}
