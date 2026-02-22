<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers;

use App\Actions\Backoffice\Shared\Quotations\UpsertQuotationAction;
use App\Actions\Backoffice\Shared\Invoices\UpsertInvoiceAction;
use App\Actions\Backoffice\Shared\Documents\DeleteQuotationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Quotations\CreateDealerQuotationsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Quotations\ConvertDealerQuotationsToInvoiceRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Quotations\DestroyDealerQuotationsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Quotations\EditDealerQuotationsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Quotations\ExportDealerQuotationsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Quotations\IndexDealerQuotationsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Quotations\StoreDealerQuotationsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Quotations\UpdateDealerQuotationsRequest;
use App\Http\Resources\Backoffice\Shared\Quotations\QuotationEditResource;
use App\Http\Resources\Backoffice\Shared\Quotations\QuotationIndexResource;
use App\Models\Dealer\Dealer;
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

    public function index(IndexDealerQuotationsRequest $request, Dealer $dealer): Response
    {
        $actor = $request->user('backoffice');
        $filters = $request->validated();
        $documentSettings = $this->documentSettings->dealer($dealer->id);
        $canCreate = $actor->hasPermissionTo('createDealershipQuotations', 'backoffice');
        $canEdit = $actor->hasPermissionTo('editDealershipQuotations', 'backoffice');
        $canDelete = $actor->hasPermissionTo('deleteDealershipQuotations', 'backoffice');
        $canShowNotes = $actor->hasPermissionTo('showNotes', 'backoffice');

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
                'valid_until',
                'customer_firstname',
                'customer_lastname',
                'total_amount',
            ],
            allSortable: true,
            numericKeys: ['total_amount']
        );

        return Inertia::render('Shared/Quotations/Index', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'quotations',
            'context' => [
                'mode' => 'dealer-backoffice',
                'showDealerColumn' => false,
            ],
            'records' => $records,
            'filters' => $filters,
            'columns' => $columns,
            'createRoute' => route('backoffice.dealer-management.dealers.quotations.create', $dealer),
            'editRouteName' => 'backoffice.dealer-management.dealers.quotations.edit',
            'deleteRouteName' => 'backoffice.dealer-management.dealers.quotations.destroy',
            'exportRouteName' => 'backoffice.dealer-management.dealers.quotations.export',
            'canCreate' => $canCreate,
            'currencySymbol' => $documentSettings['currencySymbol'],
        ]);
    }

    public function create(CreateDealerQuotationsRequest $request, Dealer $dealer): Response
    {
        $vatSnapshot = $this->vatSnapshotResolver->forDealer($dealer);
        $documentSettings = $this->documentSettings->dealer($dealer->id, includeContactNoPrefix: true);
        $canCreateCustomer = $request->user('backoffice')?->hasPermissionTo('createDealershipCustomers', 'backoffice') ?? false;

        return Inertia::render('Shared/Quotations/Form', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'context' => [
                'mode' => 'dealer-backoffice',
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
            'indexRoute' => route('backoffice.dealer-management.dealers.quotations.index', $dealer),
            'storeRoute' => route('backoffice.dealer-management.dealers.quotations.store', $dealer),
            'updateRoute' => null,
            'destroyRoute' => null,
            'exportRoute' => null,
            'customerSearchRoute' => route('backoffice.dealer-management.dealers.quotations.customers.search', $dealer),
            'customerStoreRoute' => route('backoffice.dealer-management.dealers.quotations.customers.store', $dealer),
            'lineItemSuggestionRoute' => route('backoffice.dealer-management.dealers.quotations.line-item-suggestions', $dealer),
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.quotations.index', $dealer)),
            'currencySymbol' => $documentSettings['currencySymbol'],
            'contactNoPrefix' => $documentSettings['contactNoPrefix'],
        ]);
    }

    public function store(StoreDealerQuotationsRequest $request, Dealer $dealer): RedirectResponse
    {
        $vatSnapshot = $this->vatSnapshotResolver->forDealer($dealer);
        $actor = $request->user('backoffice');

        $this->upsertQuotationAction->execute(
            quotation: null,
            data: $request->validated(),
            actor: $actor,
            dealer: $dealer,
            vatSnapshot: $vatSnapshot
        );

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.quotations.index', $dealer)))
            ->with('success', 'Quotation created.');
    }

    public function edit(EditDealerQuotationsRequest $request, Dealer $dealer, Quotation $quotation): Response|RedirectResponse
    {
        $documentSettings = $this->documentSettings->dealer($dealer->id, includeContactNoPrefix: true);
        $canEditQuotation = $this->editabilityService->dealerCanEdit($quotation, $dealer);
        $currentVatSnapshot = $this->vatSnapshotResolver->forDealer($dealer);
        $quotationVatSnapshot = [
            'vat_enabled' => (bool) $quotation->vat_enabled,
            'vat_percentage' => $quotation->vat_percentage !== null ? (float) $quotation->vat_percentage : null,
        ];
        $isVatSnapshotMismatch = ! $this->vatSnapshotResolver->hasMatchingVatSnapshot($currentVatSnapshot, $quotationVatSnapshot);
        $readOnlyReason = ! $canEditQuotation && $isVatSnapshotMismatch
            ? 'This quotation is read-only because VAT settings changed since it was created.'
            : null;

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

        return Inertia::render('Shared/Quotations/Form', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'context' => [
                'mode' => 'dealer-backoffice',
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
            'canEdit' => $canEditQuotation,
            'readOnlyReason' => $readOnlyReason,
            'canDelete' => true,
            'canExport' => true,
            'canShowNotes' => true,
            'canCreateCustomer' => $request->user('backoffice')?->hasPermissionTo('createDealershipCustomers', 'backoffice') ?? false,
            'canConvertToInvoice' => ($request->user('backoffice')?->hasPermissionTo('createDealershipInvoices', 'backoffice') ?? false) && $canEditQuotation,
            'convertToInvoiceRoute' => route('backoffice.dealer-management.dealers.quotations.convert-to-invoice', [$dealer, $quotation]),
            'linkedInvoices' => $quotation->invoices
                ->sortByDesc('invoice_date')
                ->map(fn ($invoice) => [
                    'id' => $invoice->id,
                    'invoice_identifier' => (string) $invoice->invoice_identifier,
                    'invoice_date' => optional($invoice->invoice_date)?->format('Y-m-d'),
                    'url' => route('backoffice.dealer-management.dealers.invoices.edit', ['dealer' => $dealer->id, 'invoice' => $invoice->id, 'return_to' => $request->fullUrl()]),
                ])
                ->values()
                ->all(),
            'indexRoute' => route('backoffice.dealer-management.dealers.quotations.index', $dealer),
            'storeRoute' => route('backoffice.dealer-management.dealers.quotations.store', $dealer),
            'updateRoute' => route('backoffice.dealer-management.dealers.quotations.update', [$dealer, $quotation]),
            'destroyRoute' => route('backoffice.dealer-management.dealers.quotations.destroy', [$dealer, $quotation]),
            'exportRoute' => route('backoffice.dealer-management.dealers.quotations.export', [$dealer, $quotation]),
            'customerSearchRoute' => route('backoffice.dealer-management.dealers.quotations.customers.search', $dealer),
            'customerStoreRoute' => route('backoffice.dealer-management.dealers.quotations.customers.store', $dealer),
            'lineItemSuggestionRoute' => route('backoffice.dealer-management.dealers.quotations.line-item-suggestions', $dealer),
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.quotations.index', $dealer)),
            'currencySymbol' => $documentSettings['currencySymbol'],
            'contactNoPrefix' => $documentSettings['contactNoPrefix'],
        ]);
    }

    public function update(UpdateDealerQuotationsRequest $request, Dealer $dealer, Quotation $quotation): RedirectResponse
    {
        if (! $this->editabilityService->dealerCanEdit($quotation, $dealer)) {
            return back()->with('error', 'This quotation can no longer be edited because VAT settings changed since it was created.');
        }

        $actor = $request->user('backoffice');
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

    public function destroy(DestroyDealerQuotationsRequest $request, Dealer $dealer, Quotation $quotation, DeleteQuotationAction $action): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $action->execute($quotation, $dealer);

        return back()->with('success', 'Quotation deleted.');
    }

    public function export(ExportDealerQuotationsRequest $request, Dealer $dealer, Quotation $quotation): RedirectResponse
    {
        return back()->with('success', 'Export endpoint is prepared. PDF generation will be added next.');
    }

    public function convertToInvoice(ConvertDealerQuotationsToInvoiceRequest $request, Dealer $dealer, Quotation $quotation): RedirectResponse
    {
        if (! $this->editabilityService->dealerCanEdit($quotation, $dealer)) {
            return back()->with('error', 'This quotation cannot be converted to an invoice because VAT settings changed since it was created.');
        }

        $quotation->load('lineItems');
        $actor = $request->user('backoffice');
        $vatSnapshot = $this->vatSnapshotResolver->forDealer($dealer);

        $invoice = $this->upsertInvoiceAction->execute(
            invoice: null,
            data: [
                'customer_id' => $quotation->customer_id,
                'has_custom_invoice_identifier' => (bool) $request->boolean('has_custom_invoice_identifier'),
                'invoice_identifier' => $request->input('invoice_identifier'),
                'invoice_date' => now()->toDateString(),
                'payable_by' => optional($quotation->valid_until)?->format('Y-m-d'),
                'purchase_order_number' => null,
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

        return redirect()->route('backoffice.dealer-management.dealers.invoices.edit', [
            'dealer' => $dealer->id,
            'invoice' => $invoice->id,
            'return_to' => $request->input('return_to', route('backoffice.dealer-management.dealers.quotations.index', $dealer)),
        ])->with('success', 'Invoice created from quotation.');
    }
}
