<?php

namespace App\Http\Controllers\Backoffice\DealerConfiguration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\DealerConfiguration\Branches\CreateDealerConfigurationBranchesRequest;
use App\Http\Requests\Backoffice\DealerConfiguration\Branches\DestroyDealerConfigurationBranchesRequest;
use App\Http\Requests\Backoffice\DealerConfiguration\Branches\EditDealerConfigurationBranchesRequest;
use App\Http\Requests\Backoffice\DealerConfiguration\Branches\IndexDealerConfigurationBranchesRequest;
use App\Http\Requests\Backoffice\DealerConfiguration\Branches\StoreDealerConfigurationBranchesRequest;
use App\Http\Requests\Backoffice\DealerConfiguration\Branches\UpdateDealerConfigurationBranchesRequest;
use App\Models\Dealer\DealerBranch;
use App\Models\Location\LocationCity;
use App\Models\Location\LocationCountry;
use App\Models\Location\LocationState;
use App\Models\Location\LocationSuburb;
use App\Support\Options\StockOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BranchesController extends Controller
{
    public function index(IndexDealerConfigurationBranchesRequest $request): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexBranches', $dealer);

        $filters = $request->validated();

        $query = $dealer->branches()
            ->select(['id', 'dealer_id', 'suburb_id', 'name'])
            ->with([
                'suburb:id,name,city_id',
                'suburb.city:id,name,state_id',
                'suburb.city.state:id,name,country_id',
                'suburb.city.state.country:id,name',
            ])
            ->withCount(['salePeople', 'notes'])
            ->filterSearch($filters['search'] ?? null, ['name']);

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

        $records = $query->paginate((int) ($filters['rowsPerPage'] ?? 25))->appends($filters);
        $records->setCollection(
            $records->getCollection()->map(function (DealerBranch $branch) use ($actor) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'country' => $branch->suburb?->city?->state?->country?->name ?? '-',
                    'state' => $branch->suburb?->city?->state?->name ?? '-',
                    'city' => $branch->suburb?->city?->name ?? '-',
                    'suburb' => $branch->suburb?->name ?? '-',
                    'sale_people_count' => (int) ($branch->sale_people_count ?? 0),
                    'notes_count' => (int) ($branch->notes_count ?? 0),
                    'can' => [
                        'edit' => Gate::forUser($actor)->inspect('dealerConfigurationEditBranch', $branch)->allowed(),
                        'delete' => Gate::forUser($actor)->inspect('dealerConfigurationDeleteBranch', $branch)->allowed(),
                        'show_notes' => false,
                    ],
                ];
            })
        );

        $columns = collect(['name', 'country', 'state', 'city', 'suburb', 'sale_people_count', 'notes_count'])
            ->map(fn (string $key) => [
                'name' => $key,
                'label' => Str::headline($key),
                'sortable' => in_array($key, ['name', 'sale_people_count', 'notes_count'], true),
                'align' => Str::endsWith($key, '_count') ? 'right' : 'left',
                'field' => $key,
                'numeric' => Str::endsWith($key, '_count'),
            ])->values()->all();

        return Inertia::render('DealerConfiguration/Branches/Index', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'filters' => $filters,
            'columns' => $columns,
            'records' => $records,
            'typeOptions' => StockOptions::types(withAll: true)->resolve(),
            'options' => [
                'countries' => LocationCountry::query()->select(['id as value', 'name as label'])->orderBy('name')->get()->toArray(),
                'states' => LocationState::query()->select(['id as value', 'name as label', 'country_id'])->orderBy('name')->get()->toArray(),
                'cities' => LocationCity::query()->select(['id as value', 'name as label', 'state_id'])->orderBy('name')->get()->toArray(),
                'suburbs' => LocationSuburb::query()->select(['id as value', 'name as label', 'city_id'])->orderBy('name')->get()->toArray(),
            ],
        ]);
    }

    public function create(CreateDealerConfigurationBranchesRequest $request): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexBranches', $dealer);

        return Inertia::render('DealerConfiguration/Branches/Create', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.branches.index')),
            'options' => [
                'countries' => LocationCountry::query()->select(['id as value', 'name as label'])->orderBy('name')->get()->toArray(),
                'states' => LocationState::query()->select(['id as value', 'name as label', 'country_id'])->orderBy('name')->get()->toArray(),
                'cities' => LocationCity::query()->select(['id as value', 'name as label', 'state_id'])->orderBy('name')->get()->toArray(),
                'suburbs' => LocationSuburb::query()->select(['id as value', 'name as label', 'city_id'])->orderBy('name')->get()->toArray(),
            ],
        ]);
    }

    public function store(StoreDealerConfigurationBranchesRequest $request): RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexBranches', $dealer);

        $data = $request->safe()->except(['return_to']);
        $dealer->branches()->create($data);

        return redirect($request->input('return_to', route('backoffice.dealer-configuration.branches.index')))
            ->with('success', 'Branch created.');
    }

    public function edit(EditDealerConfigurationBranchesRequest $request, DealerBranch $branch): Response
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditBranch', $branch);

        return Inertia::render('DealerConfiguration/Branches/Edit', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $actor->dealer->id, 'name' => $actor->dealer->name],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.branches.index')),
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

    public function update(UpdateDealerConfigurationBranchesRequest $request, DealerBranch $branch): RedirectResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditBranch', $branch);

        $data = $request->safe()->except(['return_to']);
        $branch->update($data);

        return redirect($request->input('return_to', route('backoffice.dealer-configuration.branches.index')))
            ->with('success', 'Branch updated.');
    }

    public function destroy(DestroyDealerConfigurationBranchesRequest $request, DealerBranch $branch): RedirectResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationDeleteBranch', $branch);

        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $branch->delete();

        return back()->with('success', 'Branch deleted.');
    }
}
