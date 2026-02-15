<?php

namespace App\Http\Controllers\Backoffice\DealerManagement;

use App\Actions\DealerManagement\Dealers\AssignDealerWhatsappNumberAction;
use App\Actions\DealerManagement\Dealers\CreateDealerAction;
use App\Actions\DealerManagement\Dealers\CreateDealerBranchAction;
use App\Actions\DealerManagement\Dealers\CreateDealerSalePersonAction;
use App\Actions\DealerManagement\Dealers\CreateDealerUserAction;
use App\Actions\DealerManagement\Dealers\DeleteDealerAction;
use App\Actions\DealerManagement\Dealers\SetDealerActiveStatusAction;
use App\Actions\DealerManagement\Dealers\UpdateDealerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\CreateDealersManagementRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\DeactivateDealersManagementRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\DestroyDealersManagementRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\EditDealersManagementRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\IndexDealersManagementRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\StoreDealersManagementRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\UpdateDealersManagementRequest;
use App\Http\Requests\Backoffice\DealerManagement\Dealers\ActivateDealersManagementRequest;
use App\Http\Resources\Backoffice\DealerManagement\Dealers\DealerIndexResource;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\Location\LocationCity;
use App\Models\Location\LocationCountry;
use App\Models\Location\LocationState;
use App\Models\Location\LocationSuburb;
use App\Models\WhatsappNumber;
use App\Support\DeferredDatasets\DeferredDealerStockCount;
use App\Support\DeferredDatasets\DeferredBranchesCount;
use App\Support\DeferredDatasets\DeferredUsersCount;
use App\Support\Options\GeneralOptions;
use App\Support\Options\StockOptions;
use App\Support\Services\DealerLeadDefaultsProvisioner;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DealersController extends Controller
{
    public function index(IndexDealersManagementRequest $request): Response
    {
        $filters = $request->validated();
        if (! $request->has('status')) {
            $filters['status'] = 'active';
        }

        $query = Dealer::query()
            ->select(['id', 'name', 'is_active'])
            ->withCount('branches')
            ->withCount([
                'users as users_count' => fn (Builder $builder) => $builder->active(),
                'notes',
            ])
            ->filterActiveStatus($filters['status'] ?? null)
            ->filterSearch($filters['search'] ?? null, ['name']);

        $countryId = $filters['country_id'] ?? null;
        $stateId = $filters['state_id'] ?? null;
        $cityId = $filters['city_id'] ?? null;
        $suburbId = $filters['suburb_id'] ?? null;

        if ($countryId || $stateId || $cityId || $suburbId) {
            $query->whereHas('branches', fn (Builder $builder) => $builder->filterLocations($countryId, $stateId, $cityId, $suburbId));
        }

        $sortBy = $filters['sortBy'] ?? 'name';
        $direction = filter_var($filters['descending'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';

        match ($sortBy) {
            'status' => $query->orderBy('is_active', $direction),
            'branches_count' => $query->orderBy('branches_count', $direction),
            'users_count' => $query->orderBy('users_count', $direction),
            'notes_count' => $query->orderBy('notes_count', $direction),
            default => $query->orderBy('name', $direction),
        };

        $records = $query
            ->paginate((int) ($filters['rowsPerPage'] ?? 5))
            ->appends($filters);

        $records->setCollection(
            $records->getCollection()->map(
                fn (Dealer $dealer) => (new DealerIndexResource($dealer))->toArray($request)
            )
        );

        $columns = collect([
            'name',
            'status',
            'branches_count',
            'users_count',
            'active_stock_count',
            'inactive_stock_count',
            'total_stock_count',
            'published_count',
            'unpublished_count',
            'notes_count',
        ])->map(fn (string $key) => [
            'name' => $key,
            'label' => Str::headline($key),
            'sortable' => in_array($key, ['name', 'status', 'branches_count', 'users_count', 'notes_count'], true),
            'align' => Str::endsWith($key, '_count') ? 'right' : 'left',
            'field' => $key,
            'numeric' => Str::endsWith($key, '_count'),
        ])->values()->all();

        $dealerIds = $records->getCollection()->pluck('id')->values();
        $stockType = $filters['type'] ?? null;

        return Inertia::render('DealerManagement/Dealers/Index', [
            'publicTitle' => 'Dealer Management',
            'filters' => $filters,
            'columns' => $columns,
            'records' => $records,
            'statusOptions' => GeneralOptions::activeOptions(withAll: true)->resolve(),
            'typeOptions' => StockOptions::types(withAll: true)->resolve(),
            'options' => [
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
                'suburbs' => LocationSuburb::query()
                    ->select(['id as value', 'name as label', 'city_id'])
                    ->orderBy('name')
                    ->get()
                    ->toArray(),
            ],
            'deferredStockCount' => DeferredDealerStockCount::resolve($dealerIds, true, $stockType),
            'deferredBranchesCount' => DeferredBranchesCount::resolve($dealerIds, true),
            'deferredUsersCount' => DeferredUsersCount::resolve($dealerIds, true),
        ]);
    }

    public function create(CreateDealersManagementRequest $request): Response
    {
        return Inertia::render('DealerManagement/Dealers/Create', [
            'publicTitle' => 'Dealer Management',
            'returnTo' => $this->resolveReturnTo($request),
            'options' => [
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
                'suburbs' => LocationSuburb::query()
                    ->select(['id as value', 'name as label', 'city_id'])
                    ->orderBy('name')
                    ->get()
                    ->toArray(),
                'whatsappNumbers' => WhatsappNumber::query()
                    ->select(['id as value', 'msisdn as label'])
                    ->where('type', WhatsappNumber::TYPE_DEALER)
                    ->whereNull('dealer_id')
                    ->whereNull('deleted_at')
                    ->orderBy('msisdn')
                    ->get()
                    ->toArray(),
            ],
        ]);
    }

    public function store(
        StoreDealersManagementRequest $request,
        CreateDealerAction $createDealerAction,
        CreateDealerBranchAction $createDealerBranchAction,
        CreateDealerUserAction $createDealerUserAction,
        CreateDealerSalePersonAction $createDealerSalePersonAction,
        AssignDealerWhatsappNumberAction $assignDealerWhatsappNumberAction,
        DealerLeadDefaultsProvisioner $dealerLeadDefaultsProvisioner,
        ConnectionInterface $db
    ): RedirectResponse {
        $data = $request->validated();
        $userEmails = [];

        $db->transaction(function () use (
            $data,
            $createDealerAction,
            $createDealerBranchAction,
            $createDealerUserAction,
            $createDealerSalePersonAction,
            $assignDealerWhatsappNumberAction,
            $dealerLeadDefaultsProvisioner,
            &$userEmails
        ) {
            $dealer = $createDealerAction->execute($data);
            $dealerLeadDefaultsProvisioner->provision($dealer);

            $branchMap = collect($data['branches'])
                ->mapWithKeys(function (array $branchData) use ($dealer, $createDealerBranchAction) {
                    $branch = $createDealerBranchAction->execute($dealer, $branchData);
                    return [(string) $branchData['client_key'] => $branch];
                });

            foreach (($data['dealer_users'] ?? []) as $dealerUserData) {
                $dealerUser = $createDealerUserAction->execute($dealer, $dealerUserData);
                $userEmails[] = (string) $dealerUser->email;
            }

            foreach (($data['sales_people'] ?? []) as $salesPersonData) {
                /** @var DealerBranch|null $branch */
                $branch = $branchMap->get((string) $salesPersonData['branch_client_key']);
                if (!$branch) {
                    continue;
                }

                $createDealerSalePersonAction->execute($branch, $salesPersonData);
            }

            if (!empty($data['whatsapp_number_id'])) {
                $whatsappNumber = WhatsappNumber::query()
                    ->whereKey($data['whatsapp_number_id'])
                    ->where('type', WhatsappNumber::TYPE_DEALER)
                    ->whereNull('dealer_id')
                    ->first();

                $assignDealerWhatsappNumberAction->execute($dealer, $whatsappNumber);
            }
        });

        foreach ($userEmails as $email) {
            Password::broker('dealers')->sendResetLink(['email' => $email]);
        }

        return redirect($this->resolveReturnTo($request))
            ->with('success', 'Dealer created.');
    }

    public function edit(EditDealersManagementRequest $request, Dealer $dealer): Response
    {
        return Inertia::render('DealerManagement/Dealers/Edit', [
            'publicTitle' => 'Dealer Management',
            'returnTo' => $this->resolveReturnTo($request),
            'data' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
        ]);
    }

    public function update(
        UpdateDealersManagementRequest $request,
        Dealer $dealer,
        UpdateDealerAction $action
    ): RedirectResponse {
        $action->execute($dealer, $request->validated());

        return redirect($this->resolveReturnTo($request))
            ->with('success', 'Dealer updated.');
    }

    public function activate(
        ActivateDealersManagementRequest $request,
        Dealer $dealer,
        SetDealerActiveStatusAction $action
    ): RedirectResponse {
        $action->execute($dealer, true);

        return back()->with('success', 'Dealer activated.');
    }

    public function deactivate(
        DeactivateDealersManagementRequest $request,
        Dealer $dealer,
        SetDealerActiveStatusAction $action
    ): RedirectResponse {
        $action->execute($dealer, false);

        return back()->with('success', 'Dealer deactivated.');
    }

    public function destroy(
        DestroyDealersManagementRequest $request,
        Dealer $dealer,
        DeleteDealerAction $action
    ): RedirectResponse {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $action->execute($dealer);

        return back()->with('success', 'Dealer deleted.');
    }

    private function resolveReturnTo(Request $request): string
    {
        $returnTo = $request->input('return_to');

        if (is_string($returnTo) && $returnTo !== '' && str_starts_with($returnTo, '/')) {
            return $returnTo;
        }

        return route('backoffice.dealer-management.dealers.index');
    }
}
