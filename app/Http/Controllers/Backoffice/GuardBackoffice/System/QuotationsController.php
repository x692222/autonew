<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\System;

use App\Actions\Backoffice\Shared\Quotations\UpsertQuotationAction;
use App\Actions\Backoffice\Shared\Invoices\UpsertInvoiceAction;
use App\Actions\Backoffice\Shared\Documents\DeleteQuotationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Quotations\CreateSystemQuotationsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Quotations\ConvertSystemQuotationsToInvoiceRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Quotations\DestroySystemQuotationsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Quotations\EditSystemQuotationsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Quotations\ExportSystemQuotationsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Quotations\IndexSystemQuotationsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Quotations\StoreSystemQuotationsRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Quotations\UpdateSystemQuotationsRequest;
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

    public function index(IndexSystemQuotationsRequest $request): Response
    {
        $actor = $request->user('backoffice');
        $filters = $request->validated();
        $documentSettings = $this->documentSettings->system();
        $canCreate = $actor->hasPermissionTo('createSystemQuotations', 'backoffice');
        $canEdit = $actor->hasPermissionTo('editSystemQuotations', 'backoffice');
        $canDelete = $actor->hasPermissionTo('deleteSystemQuotations', 'backoffice');

        $records = $this->indexService->paginate(
            query: Quotation::query()->system(),
            filters: $filters
        );

        $request->attributes->set('quotation_context', [
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_export' => $canEdit,
            'can_show_notes' => true,
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
            'publicTitle' => 'Quotations',
            'context' => [
                'mode' => 'system',
                'showDealerColumn' => false,
            ],
            'records' => $records,
            'filters' => $filters,
            'columns' => $columns,
            'createRoute' => route('backoffice.system.quotations.create'),
            'editRouteName' => 'backoffice.system.quotations.edit',
            'deleteRouteName' => 'backoffice.system.quotations.destroy',
            'exportRouteName' => 'backoffice.system.quotations.export',
            'canCreate' => $canCreate,
            'currencySymbol' => $documentSettings['currencySymbol'],
        ]);
    }

    public function create(CreateSystemQuotationsRequest $request): Response
    {
        $vatSnapshot = $this->vatSnapshotResolver->forSystem();
        $documentSettings = $this->documentSettings->system(includeContactNoPrefix: true);
        $canCreateCustomer = $request->user('backoffice')?->hasPermissionTo('createSystemCustomers', 'backoffice') ?? false;

        return Inertia::render('Shared/Quotations/Form', [
            'publicTitle' => 'Quotations',
            'context' => [
                'mode' => 'system',
                'showDealerAssociatedStock' => false,
            ],
            'data' => null,
            'customerTypeOptions' => GeneralOptions::quotationCustomerTypes()->resolve(),
            'sectionOptions' => QuotationSectionOptions::system(),
            'vat' => $vatSnapshot,
            'canEdit' => true,
            'canDelete' => false,
            'canExport' => false,
            'canShowNotes' => false,
            'canCreateCustomer' => $canCreateCustomer,
            'indexRoute' => route('backoffice.system.quotations.index'),
            'storeRoute' => route('backoffice.system.quotations.store'),
            'updateRoute' => null,
            'destroyRoute' => null,
            'exportRoute' => null,
            'customerSearchRoute' => route('backoffice.system.quotations.customers.search'),
            'customerStoreRoute' => route('backoffice.system.quotations.customers.store'),
            'lineItemSuggestionRoute' => route('backoffice.system.quotations.line-item-suggestions'),
            'returnTo' => $request->input('return_to', route('backoffice.system.quotations.index')),
            'currencySymbol' => $documentSettings['currencySymbol'],
            'contactNoPrefix' => $documentSettings['contactNoPrefix'],
        ]);
    }

    public function store(StoreSystemQuotationsRequest $request): RedirectResponse
    {
        $vatSnapshot = $this->vatSnapshotResolver->forSystem();
        $actor = $request->user('backoffice');

        $this->upsertQuotationAction->execute(
            quotation: null,
            data: $request->validated(),
            actor: $actor,
            dealer: null,
            vatSnapshot: $vatSnapshot
        );

        return redirect($request->input('return_to', route('backoffice.system.quotations.index')))
            ->with('success', 'Quotation created.');
    }

    public function edit(EditSystemQuotationsRequest $request, Quotation $quotation): Response|RedirectResponse
    {
        $documentSettings = $this->documentSettings->system(includeContactNoPrefix: true);
        $allowedSections = QuotationSectionOptions::valuesForSystem();

        $quotation->load([
            'customer',
            'invoices',
            'lineItems' => fn ($query) => $query
                ->whereIn('section', $allowedSections)
                ->with([
                    'stock',
                    'stock.vehicleItem.make',
                    'stock.vehicleItem.model',
                    'stock.commercialItem.make',
                    'stock.commercialItem.model',
                    'stock.motorbikeItem.make',
                    'stock.motorbikeItem.model',
                ]),
        ]);

        if (! $this->editabilityService->systemCanEdit($quotation)) {
            return redirect($request->input('return_to', route('backoffice.system.quotations.index')))
                ->with('error', 'This quotation can no longer be edited because VAT settings changed since it was created.');
        }

        return Inertia::render('Shared/Quotations/Form', [
            'publicTitle' => 'Quotations',
            'context' => [
                'mode' => 'system',
                'showDealerAssociatedStock' => true,
            ],
            'data' => (new QuotationEditResource($quotation))->resolve(),
            'customerTypeOptions' => GeneralOptions::quotationCustomerTypes()->resolve(),
            'sectionOptions' => QuotationSectionOptions::system(),
            'vat' => [
                'vat_enabled' => (bool) $quotation->vat_enabled,
                'vat_percentage' => $quotation->vat_percentage !== null ? (float) $quotation->vat_percentage : null,
                'vat_number' => $quotation->vat_number,
            ],
            'canEdit' => true,
            'canDelete' => true,
            'canExport' => true,
            'canShowNotes' => true,
            'canCreateCustomer' => $request->user('backoffice')?->hasPermissionTo('createSystemCustomers', 'backoffice') ?? false,
            'canConvertToInvoice' => $request->user('backoffice')?->hasPermissionTo('createSystemInvoices', 'backoffice') ?? false,
            'convertToInvoiceRoute' => route('backoffice.system.quotations.convert-to-invoice', $quotation),
            'linkedInvoices' => $quotation->invoices
                ->sortByDesc('invoice_date')
                ->map(fn ($invoice) => [
                    'id' => $invoice->id,
                    'invoice_identifier' => (string) $invoice->invoice_identifier,
                    'invoice_date' => optional($invoice->invoice_date)?->format('Y-m-d'),
                    'url' => route('backoffice.system.invoices.edit', ['invoice' => $invoice->id, 'return_to' => $request->fullUrl()]),
                ])
                ->values()
                ->all(),
            'indexRoute' => route('backoffice.system.quotations.index'),
            'storeRoute' => route('backoffice.system.quotations.store'),
            'updateRoute' => route('backoffice.system.quotations.update', $quotation),
            'destroyRoute' => route('backoffice.system.quotations.destroy', $quotation),
            'exportRoute' => route('backoffice.system.quotations.export', $quotation),
            'customerSearchRoute' => route('backoffice.system.quotations.customers.search'),
            'customerStoreRoute' => route('backoffice.system.quotations.customers.store'),
            'lineItemSuggestionRoute' => route('backoffice.system.quotations.line-item-suggestions'),
            'returnTo' => $request->input('return_to', route('backoffice.system.quotations.index')),
            'currencySymbol' => $documentSettings['currencySymbol'],
            'contactNoPrefix' => $documentSettings['contactNoPrefix'],
        ]);
    }

    public function update(UpdateSystemQuotationsRequest $request, Quotation $quotation): RedirectResponse
    {
        if (! $this->editabilityService->systemCanEdit($quotation)) {
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
            dealer: null,
            vatSnapshot: $vatSnapshot
        );

        return back()->with('success', 'Quotation updated.');
    }

    public function destroy(DestroySystemQuotationsRequest $request, Quotation $quotation, DeleteQuotationAction $action): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $action->execute($quotation);

        return back()->with('success', 'Quotation deleted.');
    }

    public function export(ExportSystemQuotationsRequest $request, Quotation $quotation): RedirectResponse
    {
        return back()->with('success', 'Export endpoint is prepared. PDF generation will be added next.');
    }

    public function convertToInvoice(ConvertSystemQuotationsToInvoiceRequest $request, Quotation $quotation): RedirectResponse
    {
        $quotation->load('lineItems');
        $actor = $request->user('backoffice');
        $vatSnapshot = $this->vatSnapshotResolver->forSystem();

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
            dealer: null,
            vatSnapshot: $vatSnapshot,
            quotation: $quotation
        );

        return back()->with('success', 'Invoice created from quotation.');
    }
}
