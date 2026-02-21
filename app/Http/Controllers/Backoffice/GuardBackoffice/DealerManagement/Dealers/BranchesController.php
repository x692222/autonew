<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Http\Controllers\Controller;
use App\Actions\Backoffice\Shared\DealerBranches\CreateDealerBranchRecordAction;
use App\Actions\Backoffice\Shared\DealerBranches\UpdateDealerBranchRecordAction;
use App\Actions\Backoffice\Shared\DealerBranches\DeleteDealerBranchRecordAction;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches\CreateDealerBranchesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches\DestroyDealerBranchesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches\EditDealerBranchesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches\IndexDealerBranchesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches\StoreDealerBranchesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches\UpdateDealerBranchesRequest;
use App\Http\Resources\Backoffice\GuardBackoffice\DealerManagement\Dealers\Branches\DealerBranchIndexResource;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\Dealer;
use App\Support\DeferredDatasets\DeferredBranchStockCount;
use App\Support\Locations\DealerBranchLocationOptionsService;
use App\Support\Options\LocationOptions;
use App\Support\Options\StockOptions;
use App\Support\Tables\DataTableColumnBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BranchesController extends Controller
{
    public function __construct(private readonly DealerBranchLocationOptionsService $locationOptionsService)
    {
    }

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

        $columns = DataTableColumnBuilder::make(
            keys: ['name', 'country', 'state', 'city', 'suburb', 'sale_people_count', 'total_stock_count', 'published_count', 'unpublished_count', 'notes_count'],
            sortableKeys: ['name', 'sale_people_count', 'notes_count'],
            numericCountSuffix: true
        );

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
            'options' => $this->locationOptionsService->forDealer($dealer),
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
                'countries' => LocationOptions::countries(null, null)->resolve(),
                'states' => LocationOptions::states(null, null)->resolve(),
                'cities' => LocationOptions::cities(null, null)->resolve(),
                'suburbs' => LocationOptions::suburbs(null, null)->resolve(),
            ],
        ]);
    }

    public function store(StoreDealerBranchesRequest $request, Dealer $dealer, CreateDealerBranchRecordAction $action): RedirectResponse
    {
        $data = $request->safe()->except(['return_to']);
        $action->execute($dealer, $data);

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
                'countries' => LocationOptions::countries(null, null)->resolve(),
                'states' => LocationOptions::states(null, null)->resolve(),
                'cities' => LocationOptions::cities(null, null)->resolve(),
                'suburbs' => LocationOptions::suburbs(null, null)->resolve(),
            ],
        ]);
    }

    public function update(UpdateDealerBranchesRequest $request, Dealer $dealer, DealerBranch $branch, UpdateDealerBranchRecordAction $action): RedirectResponse
    {
        $data = $request->safe()->except(['return_to']);
        $action->execute($dealer, $branch, $data);

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.branches', $dealer->id)))
            ->with('success', 'Branch updated.');
    }

    public function destroy(DestroyDealerBranchesRequest $request, Dealer $dealer, DealerBranch $branch, DeleteDealerBranchRecordAction $action): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $action->execute($dealer, $branch);

        return back()->with('success', 'Branch deleted.');
    }
}
