<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches\CreateDealerBranchesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches\DestroyDealerBranchesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches\EditDealerBranchesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches\IndexDealerBranchesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches\StoreDealerBranchesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches\UpdateDealerBranchesRequest;
use App\Http\Resources\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches\DealerBranchIndexResource;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\Dealer;
use App\Models\Location\LocationCity;
use App\Models\Location\LocationCountry;
use App\Models\Location\LocationState;
use App\Models\Location\LocationSuburb;
use App\Support\DeferredDatasets\DeferredBranchStockCount;
use App\Support\Options\StockOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BranchesController extends Controller
{
    public function show(IndexDealerBranchesRequest $request, Dealer $dealer): Response
    {
        $filters = $request->validated();

        $query = $dealer->branches()
            ->select(['id', 'dealer_id', 'suburb_id', 'name', 'contact_numbers', 'display_address', 'latitude', 'longitude'])
            ->with([
                'dealer:id,name',
                'suburb:id,name,city_id',
                'suburb.city:id,name,state_id',
                'suburb.city.state:id,name,country_id',
                'suburb.city.state.country:id,name',
            ])
            ->withCount(['salePeople', 'notes'])
            ->filterSearch($filters['search'] ?? null, ['name', 'contact_numbers', 'display_address']);

        $countryId = $filters['country_id'] ?? null;
        $stateId = $filters['state_id'] ?? null;
        $cityId = $filters['city_id'] ?? null;
        $suburbId = $filters['suburb_id'] ?? null;
        $stockType = $filters['type'] ?? null;

        if ($countryId || $stateId || $cityId || $suburbId) {
            $query->filterLocations($countryId, $stateId, $cityId, $suburbId);
        }

        if ($stockType) {
            $query->whereHas('stockItems', fn (Builder $builder) => $builder->where('type', $stockType));
        }

        $sortBy = $filters['sortBy'] ?? 'name';
        $direction = filter_var($filters['descending'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';

        match ($sortBy) {
            'sale_people_count' => $query->orderBy('sale_people_count', $direction),
            'notes_count' => $query->orderBy('notes_count', $direction),
            default => $query->orderBy('name', $direction),
        };

        $records = $query
            ->paginate((int) ($filters['rowsPerPage'] ?? 25))
            ->appends($filters);
        $branchIds = $records->getCollection()->pluck('id')->values();

        $records->setCollection(
            $records->getCollection()->map(
                fn (DealerBranch $branch) => (new DealerBranchIndexResource($branch))->toArray($request)
            )
        );

        $columns = collect([
            'name',
            'country',
            'state',
            'city',
            'suburb',
            'sale_people_count',
            'total_stock_count',
            'published_count',
            'unpublished_count',
            'notes_count',
        ])->map(fn (string $key) => [
            'name' => $key,
            'label' => Str::headline($key),
            'sortable' => in_array($key, ['name', 'sale_people_count', 'notes_count'], true),
            'align' => Str::endsWith($key, '_count') ? 'right' : 'left',
            'field' => $key,
            'numeric' => Str::endsWith($key, '_count'),
        ])->values()->all();

        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Tabs/Branches', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'branches',
            'filters' => $filters,
            'columns' => $columns,
            'records' => $records,
            'typeOptions' => StockOptions::types(withAll: true)->resolve(),
            'options' => $this->locationOptionsForDealer($dealer),
            'deferredStockCount' => DeferredBranchStockCount::resolve($branchIds, true, $stockType),
        ]);
    }

    public function create(CreateDealerBranchesRequest $request, Dealer $dealer): Response
    {
        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Branches/Create', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'branches',
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.branches', $dealer->id)),
            'options' => [
                'countries' => LocationCountry::query()->select(['id as value', 'name as label'])->orderBy('name')->get()->toArray(),
                'states' => LocationState::query()->select(['id as value', 'name as label', 'country_id'])->orderBy('name')->get()->toArray(),
                'cities' => LocationCity::query()->select(['id as value', 'name as label', 'state_id'])->orderBy('name')->get()->toArray(),
                'suburbs' => LocationSuburb::query()->select(['id as value', 'name as label', 'city_id'])->orderBy('name')->get()->toArray(),
            ],
        ]);
    }

    public function store(StoreDealerBranchesRequest $request, Dealer $dealer): RedirectResponse
    {
        $data = $request->safe()->except(['return_to']);
        $dealer->branches()->create($data);

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.branches', $dealer->id)))
            ->with('success', 'Branch created.');
    }

    public function edit(EditDealerBranchesRequest $request, Dealer $dealer, DealerBranch $branch): Response
    {
        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Branches/Edit', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'branches',
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.branches', $dealer->id)),
            'data' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'suburb_id' => $branch->suburb_id,
                'contact_numbers' => $branch->contact_numbers,
                'display_address' => $branch->display_address,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
            ],
            'options' => [
                'countries' => LocationCountry::query()->select(['id as value', 'name as label'])->orderBy('name')->get()->toArray(),
                'states' => LocationState::query()->select(['id as value', 'name as label', 'country_id'])->orderBy('name')->get()->toArray(),
                'cities' => LocationCity::query()->select(['id as value', 'name as label', 'state_id'])->orderBy('name')->get()->toArray(),
                'suburbs' => LocationSuburb::query()->select(['id as value', 'name as label', 'city_id'])->orderBy('name')->get()->toArray(),
            ],
        ]);
    }

    public function update(UpdateDealerBranchesRequest $request, Dealer $dealer, DealerBranch $branch): RedirectResponse
    {
        $data = $request->safe()->except(['return_to']);
        $branch->update($data);

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.branches', $dealer->id)))
            ->with('success', 'Branch updated.');
    }

    public function destroy(DestroyDealerBranchesRequest $request, Dealer $dealer, DealerBranch $branch): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $branch->delete();

        return back()->with('success', 'Branch deleted.');
    }

    private function locationOptionsForDealer(Dealer $dealer): array
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

        return [
            'countries' => $rows->map(fn ($row) => [
                'value' => $row->country_id,
                'label' => $row->country_name,
            ])->unique('value')->values()->all(),
            'states' => $rows->map(fn ($row) => [
                'value' => $row->state_id,
                'label' => $row->state_name,
                'country_id' => $row->state_country_id,
            ])->unique('value')->values()->all(),
            'cities' => $rows->map(fn ($row) => [
                'value' => $row->city_id,
                'label' => $row->city_name,
                'state_id' => $row->city_state_id,
            ])->unique('value')->values()->all(),
            'suburbs' => $rows->map(fn ($row) => [
                'value' => $row->suburb_id,
                'label' => $row->suburb_name,
                'city_id' => $row->suburb_city_id,
            ])->unique('value')->values()->all(),
        ];
    }
}
