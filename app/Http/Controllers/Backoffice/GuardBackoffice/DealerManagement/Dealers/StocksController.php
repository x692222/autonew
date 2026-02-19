<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock\ActivateDealerStockRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock\CreateDealerStockRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock\DeactivateDealerStockRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock\DestroyDealerStockRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock\EditDealerStockRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock\IndexDealerStockRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock\MarkSoldDealerStockRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock\MarkUnsoldDealerStockRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock\ShowDealerStockRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock\StoreDealerStockRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Stock\UpdateDealerStockRequest;
use App\Http\Resources\Backoffice\Shared\Stock\StockIndexResource;
use App\Models\Dealer\Dealer;
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

    public function index(IndexDealerStockRequest $request, Dealer $dealer): Response
    {
        $filters = $request->validated();
        $currencySymbol = (string) $this->dealerSettingsResolver->get((string) $dealer->id, 'dealer_currency', 'N$');

        $type = $filters['type'] ?? null;
        $makeId = $filters['make_id'] ?? null;

        $capabilities = $this->stockIndexService->capabilities($type);
        $options = $this->stockIndexService->options(
            isBackoffice: true,
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
            'include_dealer' => true,
            'can_toggle_active' => $request->user('backoffice')?->hasPermissionTo('changeStockStatus', 'backoffice') ?? false,
            'can_show_notes' => Gate::inspect('showNotes', $dealer)->allowed(),
            'can_view' => $request->user('backoffice')?->hasPermissionTo('showDealershipStock', 'backoffice') ?? false,
            'can_edit' => $request->user('backoffice')?->hasPermissionTo('editDealershipStock', 'backoffice') ?? false,
            'can_delete' => $request->user('backoffice')?->hasPermissionTo('deleteDealershipStock', 'backoffice') ?? false,
            'model_labels' => $modelLabels,
        ]);

        $records->setCollection(
            collect(StockIndexResource::collection($records->getCollection())->resolve($request))
        );

        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Tabs/Stock', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'stock',
            'isDealerView' => false,
            'showDealerFilter' => false,
            'routeName' => 'backoffice.dealer-management.dealers.stock',
            'toggleRouteNames' => [
                'activate' => 'backoffice.dealer-management.dealers.stock.activate',
                'deactivate' => 'backoffice.dealer-management.dealers.stock.deactivate',
            ],
            'createRouteName' => 'backoffice.dealer-management.dealers.stock.create',
            'showRouteName' => 'backoffice.dealer-management.dealers.stock.show',
            'editRouteName' => 'backoffice.dealer-management.dealers.stock.edit',
            'destroyRouteName' => 'backoffice.dealer-management.dealers.stock.destroy',
            'canCreate' => $request->user('backoffice')?->hasPermissionTo('createDealershipStock', 'backoffice') ?? false,
            'currencySymbol' => $currencySymbol,
            'filters' => $filters,
            'columns' => $this->stockIndexService->columns(includeDealer: false),
            'records' => $records,
            'capabilities' => $capabilities,
            ...$options,
        ]);
    }

    public function create(CreateDealerStockRequest $request, Dealer $dealer): Response
    {
        $currencySymbol = (string) $this->dealerSettingsResolver->get((string) $dealer->id, 'dealer_currency', 'N$');

        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Stock/Create', [
            'publicTitle' => 'Create Stock',
            'pageTab' => 'stock',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'currencySymbol' => $currencySymbol,
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.stock', $dealer->id)),
            ...$this->stockFormOptionsService->createOptions($dealer),
        ]);
    }

    public function store(StoreDealerStockRequest $request, Dealer $dealer): RedirectResponse
    {
        $stock = $this->stockWriteService->create($dealer, $request->validated(), (string) $request->user('backoffice')->id, 'backoffice');

        return redirect()->route('backoffice.dealer-management.dealers.stock.edit', [
            'dealer' => $dealer->id,
            'stock' => $stock->id,
            'return_to' => $request->input('return_to'),
        ])->with('success', 'Stock created successfully.');
    }

    public function show(ShowDealerStockRequest $request, Dealer $dealer, Stock $stock): Response
    {
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

        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Stock/Show', [
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

    public function edit(EditDealerStockRequest $request, Dealer $dealer, Stock $stock): Response
    {
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

        return Inertia::render('GuardBackoffice/DealerManagement/Dealers/Stock/Edit', [
            'publicTitle' => 'Edit Stock',
            'pageTab' => 'stock',
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.stock', $dealer->id)),
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
                'index' => 'backoffice.dealer-management.dealers.stock',
                'update' => 'backoffice.dealer-management.dealers.stock.update',
                'markSold' => 'backoffice.dealer-management.dealers.stock.mark-sold',
                'markUnsold' => 'backoffice.dealer-management.dealers.stock.mark-unsold',
                'show' => 'backoffice.dealer-management.dealers.stock.show',
                'images' => [
                    'index' => 'backoffice.dealer-management.dealers.stock.images.index',
                    'upload' => 'backoffice.dealer-management.dealers.stock.images.upload',
                    'assign' => 'backoffice.dealer-management.dealers.stock.images.assign',
                    'destroy' => 'backoffice.dealer-management.dealers.stock.images.destroy',
                    'reorder' => 'backoffice.dealer-management.dealers.stock.images.reorder',
                    'moveBackToBucket' => 'backoffice.dealer-management.dealers.stock.images.move-back-to-bucket',
                ],
            ],
        ]);
    }

    public function update(UpdateDealerStockRequest $request, Dealer $dealer, Stock $stock): RedirectResponse
    {
        $this->stockWriteService->update($dealer, $stock, $request->validated(), (string) $request->user('backoffice')->id, 'backoffice');

        return back()->with('success', 'Stock updated successfully.');
    }

    public function destroy(DestroyDealerStockRequest $request, Dealer $dealer, Stock $stock): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $stock->delete();

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.stock', $dealer->id)))
            ->with('success', 'Stock deleted.');
    }

    public function markSold(MarkSoldDealerStockRequest $request, Dealer $dealer, Stock $stock): RedirectResponse
    {
        $this->stockWriteService->markSold($stock);
        return back()->with('success', 'Stock item marked as SOLD');
    }

    public function markUnsold(MarkUnsoldDealerStockRequest $request, Dealer $dealer, Stock $stock): RedirectResponse
    {
        $this->stockWriteService->markUnsold($stock);
        return back()->with('success', 'Stock item marked as FOR SALE');
    }

    public function activate(ActivateDealerStockRequest $request, Dealer $dealer, Stock $stock): RedirectResponse
    {
        $stock->update(['is_active' => true]);

        return back()->with('success', 'Stock activated.');
    }

    public function deactivate(DeactivateDealerStockRequest $request, Dealer $dealer, Stock $stock): RedirectResponse
    {
        $stock->update(['is_active' => false]);

        return back()->with('success', 'Stock deactivated.');
    }
}
