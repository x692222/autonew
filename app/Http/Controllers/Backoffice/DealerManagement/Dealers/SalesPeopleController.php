<?php

namespace App\Http\Controllers\Backoffice\DealerManagement\Dealers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\SalesPeople\DestroyDealerSalesPeopleRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\SalesPeople\EditDealerSalesPeopleRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\SalesPeople\IndexDealerSalesPeopleRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\SalesPeople\UpdateDealerSalesPeopleRequest;
use App\Http\Resources\Backoffice\DealerManagement\Dealers\SalesPeople\DealerSalesPeopleIndexResource;
use App\Models\Dealer\DealerBranch;
use App\Models\Dealer\DealerSalePerson;
use App\Models\Dealer\Dealer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SalesPeopleController extends Controller
{
    public function show(IndexDealerSalesPeopleRequest $request, Dealer $dealer): Response
    {
        $filters = $request->validated();

        $query = DealerSalePerson::query()
            ->select(['id', 'branch_id', 'firstname', 'lastname', 'contact_no', 'email'])
            ->with([
                'branch:id,dealer_id,name',
                'branch.dealer:id,name',
            ])
            ->withCount('notes')
            ->whereHas('branch', fn (Builder $builder) => $builder->where('dealer_id', $dealer->id));

        $branchId = $filters['branch_id'] ?? null;
        if ($branchId) {
            $query->where('branch_id', $branchId);
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

        $records = $query
            ->paginate((int) ($filters['rowsPerPage'] ?? 25))
            ->appends($filters);

        $records->setCollection(
            $records->getCollection()->map(
                fn (DealerSalePerson $salesPerson) => (new DealerSalesPeopleIndexResource($salesPerson))->toArray($request)
            )
        );

        $columns = collect([
            'branch',
            'firstname',
            'lastname',
            'contact_no',
            'email',
            'notes_count',
        ])->map(fn (string $key) => [
            'name' => $key,
            'label' => Str::headline($key),
            'sortable' => in_array($key, ['branch', 'firstname', 'lastname', 'notes_count'], true),
            'align' => Str::endsWith($key, '_count') ? 'right' : 'left',
            'field' => $key,
            'numeric' => Str::endsWith($key, '_count'),
        ])->values()->all();

        return Inertia::render('DealerManagement/Dealers/Tabs/SalesPeople', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'sales-people',
            'filters' => $filters,
            'columns' => $columns,
            'records' => $records,
            'branchOptions' => $dealer->branches()
                ->select(['id as value', 'name as label'])
                ->orderBy('name')
                ->get()
                ->toArray(),
        ]);
    }

    public function edit(EditDealerSalesPeopleRequest $request, Dealer $dealer, DealerSalePerson $salesPerson): Response
    {
        return Inertia::render('DealerManagement/Dealers/SalesPeople/Edit', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'sales-people',
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.sales-people', $dealer->id)),
            'branchOptions' => $dealer->branches()
                ->select(['id as value', 'name as label'])
                ->orderBy('name')
                ->get()
                ->toArray(),
            'data' => [
                'id' => $salesPerson->id,
                'branch_id' => $salesPerson->branch_id,
                'firstname' => $salesPerson->firstname,
                'lastname' => $salesPerson->lastname,
                'contact_no' => $salesPerson->contact_no,
                'email' => $salesPerson->email,
            ],
        ]);
    }

    public function update(UpdateDealerSalesPeopleRequest $request, Dealer $dealer, DealerSalePerson $salesPerson): RedirectResponse
    {
        $data = $request->safe()->except(['return_to']);
        $branchBelongsToDealer = $dealer->branches()->whereKey($data['branch_id'])->exists();
        if (! $branchBelongsToDealer) {
            return back()->withErrors(['branch_id' => 'Selected branch is invalid for this dealer.'])->withInput();
        }

        $salesPerson->update($data);

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.sales-people', $dealer->id)))
            ->with('success', 'Sales person updated.');
    }

    public function destroy(DestroyDealerSalesPeopleRequest $request, Dealer $dealer, DealerSalePerson $salesPerson): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $salesPerson->delete();

        return back()->with('success', 'Sales person deleted.');
    }
}
