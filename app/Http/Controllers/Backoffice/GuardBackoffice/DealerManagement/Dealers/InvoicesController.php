<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers;

use App\Actions\Backoffice\Shared\Invoices\UpsertInvoiceAction;
use App\Enums\InvoiceCustomerTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Invoices\CreateDealerInvoicesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Invoices\DestroyDealerInvoicesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Invoices\EditDealerInvoicesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Invoices\ExportDealerInvoicesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Invoices\IndexDealerInvoicesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Invoices\StoreDealerInvoicesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\DealerManagement\Dealers\Invoices\UpdateDealerInvoicesRequest;
use App\Http\Resources\Backoffice\Shared\Invoices\InvoiceEditResource;
use App\Http\Resources\Backoffice\Shared\Invoices\InvoiceIndexResource;
use App\Models\Dealer\Dealer;
use App\Models\Invoice\Invoice;
use App\Support\Invoices\InvoiceEditabilityService;
use App\Support\Invoices\InvoiceIndexService;
use App\Support\Invoices\InvoiceSectionOptions;
use App\Support\Invoices\InvoiceVatSnapshotResolver;
use App\Support\Settings\DealerSettingsResolver;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class InvoicesController extends Controller
{
    public function __construct(
        private readonly InvoiceIndexService $indexService,
        private readonly InvoiceVatSnapshotResolver $vatSnapshotResolver,
        private readonly InvoiceEditabilityService $editabilityService,
        private readonly UpsertInvoiceAction $upsertInvoiceAction,
        private readonly DealerSettingsResolver $dealerSettingsResolver
    ) {
    }

    public function index(IndexDealerInvoicesRequest $request, Dealer $dealer): Response
    {
        $actor = $request->user('backoffice');
        $filters = $request->validated();
        $settings = $this->dealerSettingsResolver->resolve($dealer->id, ['dealer_currency']);
        $canCreate = $actor->hasPermissionTo('createDealershipInvoices', 'backoffice');
        $canEdit = $actor->hasPermissionTo('editDealershipInvoices', 'backoffice');
        $canDelete = $actor->hasPermissionTo('deleteDealershipInvoices', 'backoffice');
        $canShowNotes = $actor->hasPermissionTo('showNotes', 'backoffice');

        $records = $this->indexService->paginate(
            query: Invoice::query()->forDealer($dealer->id),
            filters: $filters
        );

        $request->attributes->set('invoice_context', [
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_export' => $canEdit,
            'can_show_notes' => $canShowNotes,
        ]);

        $records->setCollection(
            $records->getCollection()->map(
                fn (Invoice $invoice) => (new InvoiceIndexResource($invoice))->toArray($request)
            )
        );

        $columns = collect([
            'invoice_date',
            'invoice_identifier',
            'total_items_general_accessories',
            'payable_by',
            'customer_firstname',
            'customer_lastname',
            'total_amount',
        ])->map(fn (string $key) => [
            'name' => $key,
            'label' => Str::headline($key),
            'sortable' => true,
            'align' => in_array($key, ['total_items_general_accessories', 'total_amount'], true) ? 'right' : 'left',
            'field' => $key,
            'numeric' => in_array($key, ['total_items_general_accessories', 'total_amount'], true),
        ])->values()->all();

        return Inertia::render('Shared/Invoices/Index', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'pageTab' => 'invoices',
            'context' => [
                'mode' => 'dealer-backoffice',
                'showDealerColumn' => false,
            ],
            'records' => $records,
            'filters' => $filters,
            'columns' => $columns,
            'createRoute' => route('backoffice.dealer-management.dealers.invoices.create', $dealer),
            'editRouteName' => 'backoffice.dealer-management.dealers.invoices.edit',
            'deleteRouteName' => 'backoffice.dealer-management.dealers.invoices.destroy',
            'exportRouteName' => 'backoffice.dealer-management.dealers.invoices.export',
            'canCreate' => $canCreate,
            'currencySymbol' => (string) ($settings['dealer_currency'] ?? 'N$'),
        ]);
    }

    public function create(CreateDealerInvoicesRequest $request, Dealer $dealer): Response
    {
        $vatSnapshot = $this->vatSnapshotResolver->forDealer($dealer);
        $settings = $this->dealerSettingsResolver->resolve($dealer->id, [
            'dealer_currency',
            'contact_no_prefix',
        ]);

        return Inertia::render('Shared/Invoices/Form', [
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
            'customerTypeOptions' => collect(InvoiceCustomerTypeEnum::cases())
                ->map(fn (InvoiceCustomerTypeEnum $enumCase) => ['label' => Str::headline($enumCase->value), 'value' => $enumCase->value])
                ->values()
                ->all(),
            'sectionOptions' => InvoiceSectionOptions::dealer(),
            'vat' => $vatSnapshot,
            'canEdit' => true,
            'canDelete' => false,
            'canExport' => false,
            'canShowNotes' => false,
            'indexRoute' => route('backoffice.dealer-management.dealers.invoices.index', $dealer),
            'storeRoute' => route('backoffice.dealer-management.dealers.invoices.store', $dealer),
            'updateRoute' => null,
            'destroyRoute' => null,
            'exportRoute' => null,
            'customerSearchRoute' => route('backoffice.dealer-management.dealers.invoices.customers.search', $dealer),
            'customerStoreRoute' => route('backoffice.dealer-management.dealers.invoices.customers.store', $dealer),
            'lineItemSuggestionRoute' => route('backoffice.dealer-management.dealers.invoices.line-item-suggestions', $dealer),
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.invoices.index', $dealer)),
            'currencySymbol' => (string) ($settings['dealer_currency'] ?? 'N$'),
            'contactNoPrefix' => (string) ($settings['contact_no_prefix'] ?? ''),
        ]);
    }

    public function store(StoreDealerInvoicesRequest $request, Dealer $dealer): RedirectResponse
    {
        $vatSnapshot = $this->vatSnapshotResolver->forDealer($dealer);
        $actor = $request->user('backoffice');

        $this->upsertInvoiceAction->execute(
            invoice: null,
            data: $request->validated(),
            actor: $actor,
            dealer: $dealer,
            vatSnapshot: $vatSnapshot
        );

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.invoices.index', $dealer)))
            ->with('success', 'Invoice created.');
    }

    public function edit(EditDealerInvoicesRequest $request, Dealer $dealer, Invoice $invoice): Response|RedirectResponse
    {
        $settings = $this->dealerSettingsResolver->resolve($dealer->id, [
            'dealer_currency',
            'contact_no_prefix',
        ]);

        $invoice->load([
            'customer',
            'lineItems.stock',
            'lineItems.stock.vehicleItem.make',
            'lineItems.stock.vehicleItem.model',
            'lineItems.stock.commercialItem.make',
            'lineItems.stock.commercialItem.model',
            'lineItems.stock.motorbikeItem.make',
            'lineItems.stock.motorbikeItem.model',
        ]);

        if (! $this->editabilityService->dealerCanEdit($invoice, $dealer)) {
            return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.invoices.index', $dealer)))
                ->with('error', 'This invoice can no longer be edited because VAT settings changed since it was created.');
        }

        return Inertia::render('Shared/Invoices/Form', [
            'publicTitle' => 'Dealer Management',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'context' => [
                'mode' => 'dealer-backoffice',
                'showDealerAssociatedStock' => true,
            ],
            'data' => (new InvoiceEditResource($invoice))->resolve(),
            'customerTypeOptions' => collect(InvoiceCustomerTypeEnum::cases())
                ->map(fn (InvoiceCustomerTypeEnum $enumCase) => ['label' => Str::headline($enumCase->value), 'value' => $enumCase->value])
                ->values()
                ->all(),
            'sectionOptions' => InvoiceSectionOptions::dealer(),
            'vat' => [
                'vat_enabled' => (bool) $invoice->vat_enabled,
                'vat_percentage' => $invoice->vat_percentage !== null ? (float) $invoice->vat_percentage : null,
                'vat_number' => $invoice->vat_number,
            ],
            'canEdit' => true,
            'canDelete' => true,
            'canExport' => true,
            'canShowNotes' => true,
            'indexRoute' => route('backoffice.dealer-management.dealers.invoices.index', $dealer),
            'storeRoute' => route('backoffice.dealer-management.dealers.invoices.store', $dealer),
            'updateRoute' => route('backoffice.dealer-management.dealers.invoices.update', [$dealer, $invoice]),
            'destroyRoute' => route('backoffice.dealer-management.dealers.invoices.destroy', [$dealer, $invoice]),
            'exportRoute' => route('backoffice.dealer-management.dealers.invoices.export', [$dealer, $invoice]),
            'customerSearchRoute' => route('backoffice.dealer-management.dealers.invoices.customers.search', $dealer),
            'customerStoreRoute' => route('backoffice.dealer-management.dealers.invoices.customers.store', $dealer),
            'lineItemSuggestionRoute' => route('backoffice.dealer-management.dealers.invoices.line-item-suggestions', $dealer),
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.invoices.index', $dealer)),
            'currencySymbol' => (string) ($settings['dealer_currency'] ?? 'N$'),
            'contactNoPrefix' => (string) ($settings['contact_no_prefix'] ?? ''),
        ]);
    }

    public function update(UpdateDealerInvoicesRequest $request, Dealer $dealer, Invoice $invoice): RedirectResponse
    {
        if (! $this->editabilityService->dealerCanEdit($invoice, $dealer)) {
            return back()->with('error', 'This invoice can no longer be edited because VAT settings changed since it was created.');
        }

        $actor = $request->user('backoffice');
        $vatSnapshot = [
            'vat_enabled' => (bool) $invoice->vat_enabled,
            'vat_percentage' => $invoice->vat_percentage !== null ? (float) $invoice->vat_percentage : null,
            'vat_number' => $invoice->vat_number,
        ];

        $this->upsertInvoiceAction->execute(
            invoice: $invoice,
            data: $request->validated(),
            actor: $actor,
            dealer: $dealer,
            vatSnapshot: $vatSnapshot
        );

        return back()->with('success', 'Invoice updated.');
    }

    public function destroy(DestroyDealerInvoicesRequest $request, Dealer $dealer, Invoice $invoice): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $invoice->delete();

        return back()->with('success', 'Invoice deleted.');
    }

    public function export(ExportDealerInvoicesRequest $request, Dealer $dealer, Invoice $invoice): RedirectResponse
    {
        return back()->with('success', 'Export endpoint is prepared. PDF generation will be added next.');
    }
}
