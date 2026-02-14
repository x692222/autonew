<?php

namespace App\Http\Controllers\Backoffice\DealerConfiguration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\DealerConfiguration\SalesPeople\DestroyDealerConfigurationSalesPeopleRequest;
use App\Http\Requests\Backoffice\DealerConfiguration\SalesPeople\EditDealerConfigurationSalesPeopleRequest;
use App\Http\Requests\Backoffice\DealerConfiguration\SalesPeople\IndexDealerConfigurationSalesPeopleRequest;
use App\Http\Requests\Backoffice\DealerConfiguration\SalesPeople\UpdateDealerConfigurationSalesPeopleRequest;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerSalePerson;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SalesPeopleController extends Controller
{
    public function index(IndexDealerConfigurationSalesPeopleRequest $request): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexSalesPeople', $dealer);

        $filters = $request->validated();

        $query = DealerSalePerson::query()
            ->select(['id', 'branch_id', 'firstname', 'lastname', 'contact_no', 'email'])
            ->with('branch:id,dealer_id,name')
            ->withCount('notes')
            ->whereHas('branch', fn (Builder $builder) => $builder->where('dealer_id', $dealer->id));

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        $sortBy = $filters['sortBy'] ?? 'lastname';
        $direction = filter_var($filters['descending'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';

        match ($sortBy) {
            'firstname' => $query->orderBy('firstname', $direction),
            'branch' => $query->orderBy(
                DealerBranch::query()->select('name')->whereColumn('dealer_branches.id', 'dealer_sale_people.branch_id'),
                $direction
            ),
            'notes_count' => $query->orderBy('notes_count', $direction),
            default => $query->orderBy('lastname', $direction),
        };

        $records = $query->paginate((int) ($filters['rowsPerPage'] ?? 25))->appends($filters);
        $records->setCollection($records->getCollection()->map(function (DealerSalePerson $salesPerson) use ($actor) {
            return [
                'id' => $salesPerson->id,
                'branch' => $salesPerson->branch?->name ?? '-',
                'firstname' => $salesPerson->firstname ?? '-',
                'lastname' => $salesPerson->lastname ?? '-',
                'contact_no' => $salesPerson->contact_no ?? '-',
                'email' => $salesPerson->email ?? '-',
                'notes_count' => (int) ($salesPerson->notes_count ?? 0),
                'can' => [
                    'edit' => Gate::forUser($actor)->inspect('dealerConfigurationEditSalesPerson', $salesPerson)->allowed(),
                    'delete' => Gate::forUser($actor)->inspect('dealerConfigurationDeleteSalesPerson', $salesPerson)->allowed(),
                    'show_notes' => false,
                ],
            ];
        }));

        $columns = collect(['branch', 'firstname', 'lastname', 'contact_no', 'email', 'notes_count'])
            ->map(fn (string $key) => [
                'name' => $key,
                'label' => Str::headline($key),
                'sortable' => in_array($key, ['branch', 'firstname', 'lastname', 'notes_count'], true),
                'align' => Str::endsWith($key, '_count') ? 'right' : 'left',
                'field' => $key,
                'numeric' => Str::endsWith($key, '_count'),
            ])->values()->all();

        return Inertia::render('DealerConfiguration/SalesPeople/Index', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'filters' => $filters,
            'columns' => $columns,
            'records' => $records,
            'branchOptions' => $dealer->branches()->select(['id as value', 'name as label'])->orderBy('name')->get()->toArray(),
        ]);
    }

    public function edit(EditDealerConfigurationSalesPeopleRequest $request, DealerSalePerson $salesPerson): Response
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditSalesPerson', $salesPerson);

        return Inertia::render('DealerConfiguration/SalesPeople/Edit', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $actor->dealer->id, 'name' => $actor->dealer->name],
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.sales-people.index')),
            'data' => [
                'id' => $salesPerson->id,
                'branch_id' => $salesPerson->branch_id,
                'firstname' => $salesPerson->firstname,
                'lastname' => $salesPerson->lastname,
                'contact_no' => $salesPerson->contact_no,
                'email' => $salesPerson->email,
            ],
            'branchOptions' => $actor->dealer->branches()->select(['id as value', 'name as label'])->orderBy('name')->get()->toArray(),
        ]);
    }

    public function update(UpdateDealerConfigurationSalesPeopleRequest $request, DealerSalePerson $salesPerson): RedirectResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditSalesPerson', $salesPerson);

        $data = $request->safe()->except(['return_to']);
        $branchBelongsToDealer = $actor->dealer->branches()->whereKey($data['branch_id'])->exists();
        if (! $branchBelongsToDealer) {
            return back()->withErrors(['branch_id' => 'Selected branch is invalid.'])->withInput();
        }

        $salesPerson->update($data);

        return redirect($request->input('return_to', route('backoffice.dealer-configuration.sales-people.index')))
            ->with('success', 'Sales person updated.');
    }

    public function destroy(DestroyDealerConfigurationSalesPeopleRequest $request, DealerSalePerson $salesPerson): RedirectResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationDeleteSalesPerson', $salesPerson);

        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $salesPerson->delete();

        return back()->with('success', 'Sales person deleted.');
    }
}
