<?php

namespace App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration;

use App\Actions\Backoffice\Shared\Quotations\UpsertQuotationAction;
use App\Actions\Backoffice\Shared\Invoices\UpsertInvoiceAction;
use App\Actions\Backoffice\Shared\Documents\DeleteQuotationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Quotations\CreateDealerConfigurationQuotationsRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Quotations\ConvertDealerConfigurationQuotationsToInvoiceRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Quotations\DestroyDealerConfigurationQuotationsRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Quotations\EditDealerConfigurationQuotationsRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Quotations\ExportDealerConfigurationQuotationsRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Quotations\IndexDealerConfigurationQuotationsRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Quotations\StoreDealerConfigurationQuotationsRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Quotations\UpdateDealerConfigurationQuotationsRequest;
use App\Http\Resources\Backoffice\Shared\Quotations\QuotationEditResource;
use App\Http\Resources\Backoffice\Shared\Quotations\QuotationIndexResource;
use App\Models\Quotation\Quotation;
use App\Support\Quotations\QuotationEditabilityService;
use App\Support\Quotations\QuotationIndexService;
use App\Support\Quotations\QuotationSectionOptions;
use App\Support\Resolvers\Quotations\QuotationVatSnapshotResolver;
use App\Support\Options\GeneralOptions;
use App\Support\Settings\DocumentSettingsPresenter;
use App\Support\Tables\DataTableColumnBuilder;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class QuotationsController extends Controller
{
    public function __construct(
        private readonly QuotationIndexService $indexService,
        private readonly QuotationVatSnapshotResolver $vatSnapshotResolver,
        private readonly QuotationEditabilityService $editabilityService,
        private readonly UpsertQuotationAction $upsertQuotationAction,
        private readonly UpsertInvoiceAction $upsertInvoiceAction,
        private readonly DocumentSettingsPresenter $documentSettings
    ) {
    }

    public function index(IndexDealerConfigurationQuotationsRequest $request): Response
    {
        $actor = $request->user('dealer');
        $filters = $request->validated();
        $dealer = $actor->dealer;
        $documentSettings = $this->documentSettings->dealer($dealer->id);
        $canCreate = $actor->hasPermissionTo('createDealershipQuotations', 'dealer');
        $canEdit = $actor->hasPermissionTo('editDealershipQuotations', 'dealer');
        $canDelete = $actor->hasPermissionTo('deleteDealershipQuotations', 'dealer');
        $canShowNotes = $actor->hasPermissionTo('showNotes', 'dealer');

        $records = $this->indexService->paginate(
            query: Quotation::query()->forDealer($dealer->id),
            filters: $filters
        );

        $request->attributes->set('quotation_context', [
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_export' => $canEdit,
            'can_show_notes' => $canShowNotes,
        ]);

        $records->setCollection(
            $records->getCollection()->map(
                fn (Quotation $quotation) => (new QuotationIndexResource($quotation))->toArray($request)
            )
        );

        $columns = DataTableColumnBuilder::make(
            keys: [
                'quotation_date',
                'quote_identifier',
                'total_items_general_accessories',
                'valid_until',
                'customer_firstname',
                'customer_lastname',
                'total_amount',
            ],
            allSortable: true,
            numericKeys: ['total_items_general_accessories', 'total_amount']
        );

        return Inertia::render('Shared/Quotations/Index', [
            'publicTitle' => 'Configuration',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'context' => [
                'mode' => 'dealer',
                'showDealerColumn' => false,
            ],
            'records' => $records,
            'filters' => $filters,
            'columns' => $columns,
            'createRoute' => route('backoffice.dealer-configuration.quotations.create'),
            'editRouteName' => 'backoffice.dealer-configuration.quotations.edit',
            'deleteRouteName' => 'backoffice.dealer-configuration.quotations.destroy',
            'exportRouteName' => 'backoffice.dealer-configuration.quotations.export',
            'canCreate' => $canCreate,
            'currencySymbol' => $documentSettings['currencySymbol'],
        ]);
    }

    public function create(CreateDealerConfigurationQuotationsRequest $request): Response
    {
        $dealer = $request->user('dealer')->dealer;
        $vatSnapshot = $this->vatSnapshotResolver->forDealer($dealer);
        $documentSettings = $this->documentSettings->dealer($dealer->id, includeContactNoPrefix: true);
        $canCreateCustomer = $request->user('dealer')?->hasPermissionTo('createCustomers', 'dealer') ?? false;

        return Inertia::render('Shared/Quotations/Form', [
            'publicTitle' => 'Configuration',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'context' => [
                'mode' => 'dealer',
                'showDealerAssociatedStock' => false,
            ],
            'data' => null,
            'customerTypeOptions' => GeneralOptions::quotationCustomerTypes()->resolve(),
            'sectionOptions' => QuotationSectionOptions::dealer(),
            'vat' => $vatSnapshot,
            'canEdit' => true,
            'canDelete' => false,
            'canExport' => false,
            'canShowNotes' => false,
            'canCreateCustomer' => $canCreateCustomer,
            'indexRoute' => route('backoffice.dealer-configuration.quotations.index'),
            'storeRoute' => route('backoffice.dealer-configuration.quotations.store'),
            'updateRoute' => null,
            'destroyRoute' => null,
            'exportRoute' => null,
            'customerSearchRoute' => route('backoffice.dealer-configuration.quotations.customers.search'),
            'customerStoreRoute' => route('backoffice.dealer-configuration.quotations.customers.store'),
            'lineItemSuggestionRoute' => route('backoffice.dealer-configuration.quotations.line-item-suggestions'),
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.quotations.index')),
            'currencySymbol' => $documentSettings['currencySymbol'],
            'contactNoPrefix' => $documentSettings['contactNoPrefix'],
        ]);
    }

    public function store(StoreDealerConfigurationQuotationsRequest $request): RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        $vatSnapshot = $this->vatSnapshotResolver->forDealer($dealer);

        $this->upsertQuotationAction->execute(
            quotation: null,
            data: $request->validated(),
            actor: $actor,
            dealer: $dealer,
            vatSnapshot: $vatSnapshot
        );

        return redirect($request->input('return_to', route('backoffice.dealer-configuration.quotations.index')))
            ->with('success', 'Quotation created.');
    }

    public function edit(EditDealerConfigurationQuotationsRequest $request, Quotation $quotation): Response|RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        $documentSettings = $this->documentSettings->dealer($dealer->id, includeContactNoPrefix: true);
        $quotation->load([
            'customer',
            'invoices',
            'lineItems.stock',
            'lineItems.stock.vehicleItem.make',
            'lineItems.stock.vehicleItem.model',
            'lineItems.stock.commercialItem.make',
            'lineItems.stock.commercialItem.model',
            'lineItems.stock.motorbikeItem.make',
            'lineItems.stock.motorbikeItem.model',
        ]);

        if (! $this->editabilityService->dealerCanEdit($quotation, $dealer)) {
            return redirect($request->input('return_to', route('backoffice.dealer-configuration.quotations.index')))
                ->with('error', 'This quotation can no longer be edited because VAT settings changed since it was created.');
        }

        return Inertia::render('Shared/Quotations/Form', [
            'publicTitle' => 'Configuration',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'context' => [
                'mode' => 'dealer',
                'showDealerAssociatedStock' => true,
            ],
            'data' => (new QuotationEditResource($quotation))->resolve(),
            'customerTypeOptions' => GeneralOptions::quotationCustomerTypes()->resolve(),
            'sectionOptions' => QuotationSectionOptions::dealer(),
            'vat' => [
                'vat_enabled' => (bool) $quotation->vat_enabled,
                'vat_percentage' => $quotation->vat_percentage !== null ? (float) $quotation->vat_percentage : null,
                'vat_number' => $quotation->vat_number,
            ],
            'canEdit' => true,
            'canDelete' => true,
            'canExport' => true,
            'canShowNotes' => true,
            'canCreateCustomer' => $request->user('dealer')?->hasPermissionTo('createCustomers', 'dealer') ?? false,
            'canConvertToInvoice' => $request->user('dealer')?->hasPermissionTo('createDealershipInvoices', 'dealer') ?? false,
            'convertToInvoiceRoute' => route('backoffice.dealer-configuration.quotations.convert-to-invoice', $quotation),
            'linkedInvoices' => $quotation->invoices
                ->sortByDesc('invoice_date')
                ->map(fn ($invoice) => [
                    'id' => $invoice->id,
                    'invoice_identifier' => (string) $invoice->invoice_identifier,
                    'invoice_date' => optional($invoice->invoice_date)?->format('Y-m-d'),
                    'url' => route('backoffice.dealer-configuration.invoices.edit', ['invoice' => $invoice->id, 'return_to' => $request->fullUrl()]),
                ])
                ->values()
                ->all(),
            'indexRoute' => route('backoffice.dealer-configuration.quotations.index'),
            'storeRoute' => route('backoffice.dealer-configuration.quotations.store'),
            'updateRoute' => route('backoffice.dealer-configuration.quotations.update', $quotation),
            'destroyRoute' => route('backoffice.dealer-configuration.quotations.destroy', $quotation),
            'exportRoute' => route('backoffice.dealer-configuration.quotations.export', $quotation),
            'customerSearchRoute' => route('backoffice.dealer-configuration.quotations.customers.search'),
            'customerStoreRoute' => route('backoffice.dealer-configuration.quotations.customers.store'),
            'lineItemSuggestionRoute' => route('backoffice.dealer-configuration.quotations.line-item-suggestions'),
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.quotations.index')),
            'currencySymbol' => $documentSettings['currencySymbol'],
            'contactNoPrefix' => $documentSettings['contactNoPrefix'],
        ]);
    }

    public function update(UpdateDealerConfigurationQuotationsRequest $request, Quotation $quotation): RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;

        if (! $this->editabilityService->dealerCanEdit($quotation, $dealer)) {
            return back()->with('error', 'This quotation can no longer be edited because VAT settings changed since it was created.');
        }

        $vatSnapshot = [
            'vat_enabled' => (bool) $quotation->vat_enabled,
            'vat_percentage' => $quotation->vat_percentage !== null ? (float) $quotation->vat_percentage : null,
            'vat_number' => $quotation->vat_number,
        ];

        $this->upsertQuotationAction->execute(
            quotation: $quotation,
            data: $request->validated(),
            actor: $actor,
            dealer: $dealer,
            vatSnapshot: $vatSnapshot
        );

        return back()->with('success', 'Quotation updated.');
    }

    public function destroy(DestroyDealerConfigurationQuotationsRequest $request, Quotation $quotation, DeleteQuotationAction $action): RedirectResponse
    {
        $dealer = $request->user('dealer')->dealer;
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $action->execute($quotation, $dealer);

        return back()->with('success', 'Quotation deleted.');
    }

    public function export(ExportDealerConfigurationQuotationsRequest $request, Quotation $quotation): RedirectResponse
    {
        return back()->with('success', 'Export endpoint is prepared. PDF generation will be added next.');
    }

    public function convertToInvoice(ConvertDealerConfigurationQuotationsToInvoiceRequest $request, Quotation $quotation): RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        $quotation->load('lineItems');
        $vatSnapshot = $this->vatSnapshotResolver->forDealer($dealer);

        $this->upsertInvoiceAction->execute(
            invoice: null,
            data: [
                'customer_id' => $quotation->customer_id,
                'has_custom_invoice_identifier' => (bool) $request->boolean('has_custom_invoice_identifier'),
                'invoice_identifier' => $request->input('invoice_identifier'),
                'invoice_date' => now()->toDateString(),
                'payable_by' => optional($quotation->valid_until)?->format('Y-m-d'),
                'purchase_order_number' => null,
                'payment_method' => null,
                'payment_terms' => null,
                'line_items' => $quotation->lineItems->map(fn ($item) => [
                    'section' => $item->section?->value ?? (string) $item->section,
                    'stock_id' => $item->stock_id,
                    'sku' => $item->sku,
                    'description' => $item->description,
                    'amount' => (float) $item->amount,
                    'qty' => (float) $item->qty,
                    'total' => (float) $item->total,
                    'is_vat_exempt' => (bool) $item->is_vat_exempt,
                ])->values()->all(),
            ],
            actor: $actor,
            dealer: $dealer,
            vatSnapshot: $vatSnapshot,
            quotation: $quotation
        );

        return back()->with('success', 'Invoice created from quotation.');
    }
}
