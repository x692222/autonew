<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement;
use App\Actions\Backoffice\GuardBackoffice\DealerManagement\Dealers\AssignDealerWhatsappNumberAction;
use App\Actions\Backoffice\GuardBackoffice\DealerManagement\Dealers\CreateDealerAction;
use App\Actions\Backoffice\GuardBackoffice\DealerManagement\Dealers\CreateDealerBranchAction;
use App\Actions\Backoffice\GuardBackoffice\DealerManagement\Dealers\CreateDealerSalePersonAction;
use App\Actions\Backoffice\GuardBackoffice\DealerManagement\Dealers\CreateDealerUserAction;
use App\Actions\Backoffice\Shared\DealerUsers\AssignAllDealerPermissionsAction;
use App\Actions\Backoffice\Shared\BankingDetails\CreateBankingDetailAction;
use App\Actions\Backoffice\GuardBackoffice\DealerManagement\Dealers\DeleteDealerAction;
use App\Actions\Backoffice\GuardBackoffice\DealerManagement\Dealers\SetDealerActiveStatusAction;
use App\Actions\Backoffice\GuardBackoffice\DealerManagement\Dealers\UpdateDealerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\CreateDealersManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\DeactivateDealersManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\DestroyDealersManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\EditDealersManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\IndexDealersManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\StoreDealersManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\UpdateDealersManagementRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\ActivateDealersManagementRequest;
use App\Http\Resources\Backoffice\GuardBackoffice\DealerManagement\Dealers\DealerIndexResource;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\WhatsappNumber;
use App\Support\DeferredDatasets\DeferredDealerStockCount;
use App\Support\DeferredDatasets\DeferredBranchesCount;
use App\Support\DeferredDatasets\DeferredUsersCount;
use App\Support\Options\DealerOptions;
use App\Support\Options\GeneralOptions;
use App\Support\Options\LocationOptions;
use App\Support\Options\StockOptions;
use App\Support\Services\DealerLeadDefaultsProvisioner;
use App\Support\Settings\ConfigurationCatalog;
use App\Support\Settings\ConfigurationManager;
use App\Support\Resolvers\System\SafeReturnToResolver;
use App\Support\Tables\DataTableColumnBuilder;
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
    public function __construct(private readonly SafeReturnToResolver $returnToResolver)
    {
    }

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

        $columns = DataTableColumnBuilder::make(
            keys: [
                'name',
                'status',
                'branches_count',
                'users_count',
                'active_stock_count',
                'inactive_stock_count',
                'total_stock_count',
                'published_count',
                'unpublished_count',
            ],
            sortableKeys: ['name', 'status', 'branches_count', 'users_count'],
            numericCountSuffix: true
        );

        $dealerIds = $records->getCollection()->pluck('id')->values();
        $stockType = $filters['type'] ?? null;

        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Index', [
            'publicTitle' => 'Dealer Management',
            'filters' => $filters,
            'columns' => $columns,
            'records' => $records,
            'statusOptions' => GeneralOptions::activeOptions(withAll: true)->resolve(),
            'typeOptions' => StockOptions::types(withAll: true)->resolve(),
            'options' => [
                'countries' => LocationOptions::countries(null, null)->resolve(),
                'states' => LocationOptions::states(null, null)->resolve(),
                'cities' => LocationOptions::cities(null, null)->resolve(),
                'suburbs' => LocationOptions::suburbs(null, null)->resolve(),
            ],
            'deferredStockCount' => DeferredDealerStockCount::resolve($dealerIds, true, $stockType),
            'deferredBranchesCount' => DeferredBranchesCount::resolve($dealerIds, true),
            'deferredUsersCount' => DeferredUsersCount::resolve($dealerIds, true),
        ]);
    }

    public function create(CreateDealersManagementRequest $request): Response
    {
        $configurationManager = app(ConfigurationManager::class);
        $catalog = app(ConfigurationCatalog::class);
        $settings = $configurationManager->dealerDefaultRows(includeBackofficeOnly: true);

        $settingsFallback = collect($catalog->dealerDefinitions())
            ->map(function (array $definition, string $key) use ($catalog) {
                $normalized = $catalog->normalizeValue($definition['type'], $definition['default'] ?? null);

                return [
                    'id' => null,
                    'key' => $key,
                    'label' => (string) $definition['label'],
                    'category' => $definition['category']->value,
                    'type' => $definition['type']->value,
                    'description' => $definition['description'] ?? null,
                    'value' => $catalog->castValue($definition['type'], $normalized),
                    'backoffice_only' => (bool) ($definition['backoffice_only'] ?? false),
                    'min' => $definition['min'] ?? null,
                    'max' => $definition['max'] ?? null,
                ];
            })
            ->sortBy([
                ['category', 'asc'],
                ['label', 'asc'],
            ])
            ->values()
            ->all();

        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Create', [
            'publicTitle' => 'Dealer Management',
            'returnTo' => $this->returnToResolver->resolve($request, 'backoffice.dealer-management.dealers.index'),
            'settings' => $settings,
            'settingsFallback' => $settingsFallback,
            'timezoneOptions' => $catalog->timezoneOptions(),
            'stockTypeOptions' => StockOptions::types(withAll: false)->resolve(),
            'options' => [
                'countries' => LocationOptions::countries(null, null)->resolve(),
                'states' => LocationOptions::states(null, null)->resolve(),
                'cities' => LocationOptions::cities(null, null)->resolve(),
                'suburbs' => LocationOptions::suburbs(null, null)->resolve(),
                'whatsappNumbers' => DealerOptions::availableWhatsappNumbers()->resolve(),
            ],
        ]);
    }

    public function store(
        StoreDealersManagementRequest $request,
        CreateDealerAction $createDealerAction,
        CreateDealerBranchAction $createDealerBranchAction,
        CreateDealerUserAction $createDealerUserAction,
        AssignAllDealerPermissionsAction $assignAllDealerPermissionsAction,
        CreateDealerSalePersonAction $createDealerSalePersonAction,
        CreateBankingDetailAction $createBankingDetailAction,
        AssignDealerWhatsappNumberAction $assignDealerWhatsappNumberAction,
        DealerLeadDefaultsProvisioner $dealerLeadDefaultsProvisioner,
        ConfigurationManager $configurationManager,
        ConnectionInterface $db
    ): RedirectResponse {
        $data = $request->validated();
        $userEmails = [];

        $db->transaction(function () use (
            $data,
            $createDealerAction,
            $createDealerBranchAction,
            $createDealerUserAction,
            $assignAllDealerPermissionsAction,
            $createDealerSalePersonAction,
            $createBankingDetailAction,
            $assignDealerWhatsappNumberAction,
            $dealerLeadDefaultsProvisioner,
            $configurationManager,
            &$userEmails
        ) {
            $dealer = $createDealerAction->execute($data);
            $dealerLeadDefaultsProvisioner->provision($dealer);
            $configurationManager->syncDealerDefaults($dealer);
            $settings = (array) ($data['settings'] ?? []);

            if (($settings['contact_no_prefix'] ?? null) === null || $settings['contact_no_prefix'] === '') {
                $settings['contact_no_prefix'] = (string) config('dealer.default_contact_no_prefix', '+264');
            }

            $configurationManager->updateDealerValues(
                dealer: $dealer,
                settings: $settings,
                includeBackofficeOnly: true
            );

            $branchMap = collect($data['branches'])
                ->mapWithKeys(function (array $branchData) use ($dealer, $createDealerBranchAction) {
                    $branch = $createDealerBranchAction->execute($dealer, $branchData);
                    return [(string) $branchData['client_key'] => $branch];
                });

            foreach (($data['dealer_users'] ?? []) as $index => $dealerUserData) {
                $dealerUser = $createDealerUserAction->execute($dealer, $dealerUserData);
                if ($index === 0) {
                    $assignAllDealerPermissionsAction->execute($dealerUser);
                }
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

            foreach (($data['banking_details'] ?? []) as $bankingDetailData) {
                $createBankingDetailAction->execute([
                    'dealer_id' => $dealer->id,
                    'bank' => $bankingDetailData['bank'],
                    'account_holder' => $bankingDetailData['account_holder'],
                    'account_number' => $bankingDetailData['account_number'],
                    'branch_name' => $bankingDetailData['branch_name'] ?? null,
                    'branch_code' => $bankingDetailData['branch_code'] ?? null,
                    'swift_code' => $bankingDetailData['swift_code'] ?? null,
                    'other_details' => $bankingDetailData['other_details'] ?? null,
                ], $dealer);
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

        return redirect($this->returnToResolver->resolve($request, 'backoffice.dealer-management.dealers.index'))
            ->with('success', 'Dealer created.');
    }

    public function edit(EditDealersManagementRequest $request, Dealer $dealer): Response
    {
        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Edit', [
            'publicTitle' => 'Dealer Management',
            'returnTo' => $this->returnToResolver->resolve($request, 'backoffice.dealer-management.dealers.index'),
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

        return redirect($this->returnToResolver->resolve($request, 'backoffice.dealer-management.dealers.index'))
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
}
