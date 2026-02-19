<?php

namespace App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Stock\CreateDealerConfigurationStockRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Stock\DestroyDealerConfigurationStockRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Stock\EditDealerConfigurationStockRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Stock\IndexDealerConfigurationStockRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Stock\MarkSoldDealerConfigurationStockRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Stock\MarkUnsoldDealerConfigurationStockRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Stock\ShowDealerConfigurationStockRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Stock\StoreDealerConfigurationStockRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Stock\UpdateDealerConfigurationStockRequest;
use App\Http\Resources\Backoffice\Shared\Stock\StockIndexResource;
use App\Models\Stock\Stock;
use App\Support\Lookups\StockLookup;
use App\Support\Settings\DealerSettingsResolver;
use App\Support\Stock\StockFormOptionsService;
use App\Support\Stock\StockIndexService;
use App\Support\Stock\StockWriteService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class StocksController extends Controller
{
    public function __construct(
        private readonly StockIndexService $stockIndexService,
        private readonly StockFormOptionsService $stockFormOptionsService,
        private readonly StockWriteService $stockWriteService,
        private readonly DealerSettingsResolver $dealerSettingsResolver,
    ) {
    }

    public function index(IndexDealerConfigurationStockRequest $request): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        $currencySymbol = (string) $this->dealerSettingsResolver->get((string) $dealer->id, 'dealer_currency', 'N$');

        $filters = $request->validated();

        $type = $filters['type'] ?? null;
        $makeId = $filters['make_id'] ?? null;

        $capabilities = $this->stockIndexService->capabilities($type);
        $options = $this->stockIndexService->options(
            isBackoffice: false,
            dealerId: (string) $dealer->id,
            type: $type,
            makeId: $makeId,
            capabilities: $capabilities,
        );

        $records = $this->stockIndexService->paginated(
            filters: $filters,
            dealerId: (string) $dealer->id,
            capabilities: $capabilities,
        );

        $modelLabels = collect($options['models'] ?? [])->filter(fn ($row) => ! empty($row['value']))->mapWithKeys(fn ($row) => [
            (string) $row['value'] => (string) ($row['label'] ?? $row['value']),
        ])->all();

        $request->attributes->set('stock_context', [
            'include_dealer' => false,
            'can_toggle_active' => false,
            'can_show_notes' => Gate::forUser($actor)->inspect('dealerConfigurationShowNotes', $dealer)->allowed(),
            'can_view' => $actor->hasPermissionTo('showStock', 'dealer'),
            'can_edit' => $actor->hasPermissionTo('editStock', 'dealer'),
            'can_delete' => $actor->hasPermissionTo('deleteStock', 'dealer'),
            'model_labels' => $modelLabels,
        ]);

        $records->setCollection(
            collect(StockIndexResource::collection($records->getCollection())->resolve($request))
        );

        return Inertia::render('GuardDealer/DealerConfiguration/Stock/Index', [
            'publicTitle' => 'Configuration',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'isDealerView' => true,
            'showDealerFilter' => false,
            'routeName' => 'backoffice.dealer-configuration.stock.index',
            'toggleRouteNames' => [
                'activate' => null,
                'deactivate' => null,
            ],
            'createRouteName' => 'backoffice.dealer-configuration.stock.create',
            'showRouteName' => 'backoffice.dealer-configuration.stock.show',
            'editRouteName' => 'backoffice.dealer-configuration.stock.edit',
            'destroyRouteName' => 'backoffice.dealer-configuration.stock.destroy',
            'canCreate' => $actor->hasPermissionTo('createStock', 'dealer'),
            'currencySymbol' => $currencySymbol,
            'filters' => $filters,
            'columns' => $this->stockIndexService->columns(includeDealer: false),
            'records' => $records,
            'capabilities' => $capabilities,
            ...$options,
        ]);
    }

    public function create(CreateDealerConfigurationStockRequest $request): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        $currencySymbol = (string) $this->dealerSettingsResolver->get((string) $dealer->id, 'dealer_currency', 'N$');

        return Inertia::render('GuardDealer/DealerConfiguration/Stock/Create', [
            'publicTitle' => 'Create Stock',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'currencySymbol' => $currencySymbol,
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.stock.index')),
            ...$this->stockFormOptionsService->createOptions($dealer),
        ]);
    }

    public function store(StoreDealerConfigurationStockRequest $request): RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;

        $stock = $this->stockWriteService->create($dealer, $request->validated(), (string) $actor->id, 'dealer');

        return redirect()->route('backoffice.dealer-configuration.stock.edit', [
            'stock' => $stock->id,
            'return_to' => $request->input('return_to'),
        ])->with('success', 'Stock created successfully.');
    }

    public function show(ShowDealerConfigurationStockRequest $request, Stock $stock): Response
    {
        $dealer = $request->user('dealer')->dealer;
        $minimumImagesRequiredForLive = max(1, (int) $this->dealerSettingsResolver->get(
            (string) $dealer->id,
            'minimum_images_required_for_live',
            config('stock.live_min_images', 3)
        ));

        $typeMeta = \App\Support\StockHelper::stockRelationMeta();
        $type = (string) $stock->type;
        $typedRelation = $typeMeta[$type]['relation'] ?? null;
        $properties = $typeMeta[$type]['properties'] ?? [];

        $relations = [
            'branch:id,name,dealer_id',
            'branch.dealer:id,name,is_active',
            'features:id,name',
        ];

        if ($typedRelation) {
            $relations[] = $typedRelation;
            if (($properties['make'] ?? false) === true) {
                $relations[] = $typedRelation . '.make:id,name';
            }
            if (($properties['model'] ?? false) === true) {
                $relations[] = $typedRelation . '.model:id,name';
            }
        }

        $stock->loadMissing($relations);

        $totalImagesCount = \Spatie\MediaLibrary\MediaCollections\Models\Media::query()
            ->where('model_type', Stock::class)
            ->where('model_id', (string) $stock->id)
            ->where('collection_name', 'stock_images')
            ->count();

        $typed = $typedRelation ? ($stock->{$typedRelation}?->toArray() ?? null) : null;
        $typedModel = $typedRelation ? $stock->{$typedRelation} : null;

        if (is_array($typed) && $typedModel) {
            $typed['make_name'] = $typedModel->make?->name;
            $typed['model_name'] = $typedModel->model?->name;
        }

        $daysSinceAcquired = null;
        if (!empty($stock->date_acquired)) {
            $daysSinceAcquired = Carbon::parse((string) $stock->date_acquired)->startOfDay()->diffInDays(now()->startOfDay());
        }

        return Inertia::render('GuardDealer/DealerConfiguration/Stock/Show', [
            'publicTitle' => 'View Stock',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name, 'is_active' => (bool) $dealer->is_active],
            'totalImagesCount' => $totalImagesCount,
            'minimumImagesRequiredForLive' => $minimumImagesRequiredForLive,
            'stock' => [
                'id' => $stock->id,
                'name' => $stock->name,
                'type' => $stock->type,
                'type_label' => \Illuminate\Support\Str::headline((string) $stock->type),
                'price' => $stock->price,
                'discounted_price' => $stock->discounted_price,
                'internal_reference' => $stock->internal_reference,
                'description' => $stock->description,
                'branch_name' => $stock->branch?->name,
                'published_at' => optional($stock->published_at)?->toDateTimeString(),
                'date_acquired' => optional($stock->date_acquired)?->toDateString(),
                'days_since_acquired' => $daysSinceAcquired,
                'is_active' => (bool) $stock->is_active,
                'is_live' => (bool) $stock->isLive($stock),
                'is_sold' => (bool) $stock->is_sold,
                'typed' => $typed,
                'feature_tags' => $stock->features->map(fn ($tag) => ['id' => $tag->id, 'name' => $tag->name])->values()->all(),
            ],
        ]);
    }

    public function edit(EditDealerConfigurationStockRequest $request, Stock $stock): Response
    {
        $dealer = $request->user('dealer')->dealer;
        $currencySymbol = (string) $this->dealerSettingsResolver->get((string) $dealer->id, 'dealer_currency', 'N$');
        $minimumImagesRequiredForLive = max(1, (int) $this->dealerSettingsResolver->get(
            (string) $dealer->id,
            'minimum_images_required_for_live',
            config('stock.live_min_images', 3)
        ));

        $typeMeta = \App\Support\StockHelper::stockRelationMeta();
        $type = (string) $stock->type;
        $typedRelation = $typeMeta[$type]['relation'] ?? null;

        $stock->loadMissing([
            'branch:id,dealer_id,name',
            $typedRelation,
            'features:id,name,stock_type,is_approved',
        ]);

        $totalImagesCount = \Spatie\MediaLibrary\MediaCollections\Models\Media::query()
            ->where('model_type', Stock::class)
            ->where('model_id', (string) $stock->id)
            ->where('collection_name', 'stock_images')
            ->count();

        return Inertia::render('GuardDealer/DealerConfiguration/Stock/Edit', [
            'publicTitle' => 'Edit Stock',
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.stock.index')),
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name, 'is_active' => (bool) $dealer->is_active],
            'currencySymbol' => $currencySymbol,
            'stock' => [
                'id' => $stock->id,
                'branch_id' => $stock->branch_id,
                'type' => $stock->type,
                'name' => $stock->name,
                'description' => $stock->description,
                'price' => $stock->price,
                'discounted_price' => $stock->discounted_price,
                'date_acquired' => optional($stock->date_acquired)?->toDateString(),
                'internal_reference' => $stock->internal_reference,
                'is_sold' => (bool) $stock->is_sold,
                'published_at' => $stock->published_at,
                'feature_ids' => $stock->features->pluck('id')->values()->all(),
                'typed' => $typedRelation ? ($stock->{$typedRelation}?->toArray() ?? []) : [],
            ],
            'canEditReference' => StockLookup::canEditReference((string) $stock->id),
            'totalImagesCount' => $totalImagesCount,
            'minimumImagesRequiredForLive' => $minimumImagesRequiredForLive,
            ...$this->stockFormOptionsService->editOptions($dealer, $stock),
            'routeNames' => [
                'index' => 'backoffice.dealer-configuration.stock.index',
                'update' => 'backoffice.dealer-configuration.stock.update',
                'markSold' => 'backoffice.dealer-configuration.stock.mark-sold',
                'markUnsold' => 'backoffice.dealer-configuration.stock.mark-unsold',
                'show' => 'backoffice.dealer-configuration.stock.show',
                'images' => [
                    'index' => 'backoffice.dealer-configuration.stock.images.index',
                    'upload' => 'backoffice.dealer-configuration.stock.images.upload',
                    'assign' => 'backoffice.dealer-configuration.stock.images.assign',
                    'destroy' => 'backoffice.dealer-configuration.stock.images.destroy',
                    'reorder' => 'backoffice.dealer-configuration.stock.images.reorder',
                    'moveBackToBucket' => 'backoffice.dealer-configuration.stock.images.move-back-to-bucket',
                ],
            ],
        ]);
    }

    public function update(UpdateDealerConfigurationStockRequest $request, Stock $stock): RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;

        $this->stockWriteService->update($dealer, $stock, $request->validated(), (string) $actor->id, 'dealer');

        return back()->with('success', 'Stock updated successfully.');
    }

    public function markSold(MarkSoldDealerConfigurationStockRequest $request, Stock $stock): RedirectResponse
    {
        $this->stockWriteService->markSold($stock);
        return back()->with('success', 'Stock item marked as SOLD');
    }

    public function markUnsold(MarkUnsoldDealerConfigurationStockRequest $request, Stock $stock): RedirectResponse
    {
        $this->stockWriteService->markUnsold($stock);
        return back()->with('success', 'Stock item marked as FOR SALE');
    }

    public function destroy(DestroyDealerConfigurationStockRequest $request, Stock $stock): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $stock->delete();

        return redirect($request->input('return_to', route('backoffice.dealer-configuration.stock.index')))
            ->with('success', 'Stock deleted.');
    }
}
