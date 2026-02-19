<?php

namespace App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration;

use App\Actions\Backoffice\Shared\Invoices\UpsertInvoiceAction;
use App\Enums\InvoiceCustomerTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Invoices\CreateDealerConfigurationInvoicesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Invoices\DestroyDealerConfigurationInvoicesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Invoices\EditDealerConfigurationInvoicesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Invoices\ExportDealerConfigurationInvoicesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Invoices\IndexDealerConfigurationInvoicesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Invoices\StoreDealerConfigurationInvoicesRequest;
use App\Http\Requests\Backoffice\GuardDealer\DealerConfiguration\Invoices\UpdateDealerConfigurationInvoicesRequest;
use App\Http\Resources\Backoffice\Shared\Invoices\InvoiceEditResource;
use App\Http\Resources\Backoffice\Shared\Invoices\InvoiceIndexResource;
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

    public function index(IndexDealerConfigurationInvoicesRequest $request): Response
    {
        $actor = $request->user('dealer');
        $filters = $request->validated();
        $dealer = $actor->dealer;
        $settings = $this->dealerSettingsResolver->resolve($dealer->id, ['dealer_currency']);
        $canCreate = $actor->hasPermissionTo('createDealershipInvoices', 'dealer');
        $canEdit = $actor->hasPermissionTo('editDealershipInvoices', 'dealer');
        $canDelete = $actor->hasPermissionTo('deleteDealershipInvoices', 'dealer');
        $canShowNotes = $actor->hasPermissionTo('showNotes', 'dealer');

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
            'createRoute' => route('backoffice.dealer-configuration.invoices.create'),
            'editRouteName' => 'backoffice.dealer-configuration.invoices.edit',
            'deleteRouteName' => 'backoffice.dealer-configuration.invoices.destroy',
            'exportRouteName' => 'backoffice.dealer-configuration.invoices.export',
            'canCreate' => $canCreate,
            'currencySymbol' => (string) ($settings['dealer_currency'] ?? 'N$'),
        ]);
    }

    public function create(CreateDealerConfigurationInvoicesRequest $request): Response
    {
        $dealer = $request->user('dealer')->dealer;
        $vatSnapshot = $this->vatSnapshotResolver->forDealer($dealer);
        $settings = $this->dealerSettingsResolver->resolve($dealer->id, [
            'dealer_currency',
            'contact_no_prefix',
        ]);

        return Inertia::render('Shared/Invoices/Form', [
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
            'indexRoute' => route('backoffice.dealer-configuration.invoices.index'),
            'storeRoute' => route('backoffice.dealer-configuration.invoices.store'),
            'updateRoute' => null,
            'destroyRoute' => null,
            'exportRoute' => null,
            'customerSearchRoute' => route('backoffice.dealer-configuration.invoices.customers.search'),
            'customerStoreRoute' => route('backoffice.dealer-configuration.invoices.customers.store'),
            'lineItemSuggestionRoute' => route('backoffice.dealer-configuration.invoices.line-item-suggestions'),
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.invoices.index')),
            'currencySymbol' => (string) ($settings['dealer_currency'] ?? 'N$'),
            'contactNoPrefix' => (string) ($settings['contact_no_prefix'] ?? ''),
        ]);
    }

    public function store(StoreDealerConfigurationInvoicesRequest $request): RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        $vatSnapshot = $this->vatSnapshotResolver->forDealer($dealer);

        $this->upsertInvoiceAction->execute(
            invoice: null,
            data: $request->validated(),
            actor: $actor,
            dealer: $dealer,
            vatSnapshot: $vatSnapshot
        );

        return redirect($request->input('return_to', route('backoffice.dealer-configuration.invoices.index')))
            ->with('success', 'Invoice created.');
    }

    public function edit(EditDealerConfigurationInvoicesRequest $request, Invoice $invoice): Response|RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
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
            return redirect($request->input('return_to', route('backoffice.dealer-configuration.invoices.index')))
                ->with('error', 'This invoice can no longer be edited because VAT settings changed since it was created.');
        }

        return Inertia::render('Shared/Invoices/Form', [
            'publicTitle' => 'Configuration',
            'dealer' => [
                'id' => $dealer->id,
                'name' => $dealer->name,
            ],
            'context' => [
                'mode' => 'dealer',
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
            'indexRoute' => route('backoffice.dealer-configuration.invoices.index'),
            'storeRoute' => route('backoffice.dealer-configuration.invoices.store'),
            'updateRoute' => route('backoffice.dealer-configuration.invoices.update', $invoice),
            'destroyRoute' => route('backoffice.dealer-configuration.invoices.destroy', $invoice),
            'exportRoute' => route('backoffice.dealer-configuration.invoices.export', $invoice),
            'customerSearchRoute' => route('backoffice.dealer-configuration.invoices.customers.search'),
            'customerStoreRoute' => route('backoffice.dealer-configuration.invoices.customers.store'),
            'lineItemSuggestionRoute' => route('backoffice.dealer-configuration.invoices.line-item-suggestions'),
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.invoices.index')),
            'currencySymbol' => (string) ($settings['dealer_currency'] ?? 'N$'),
            'contactNoPrefix' => (string) ($settings['contact_no_prefix'] ?? ''),
        ]);
    }

    public function update(UpdateDealerConfigurationInvoicesRequest $request, Invoice $invoice): RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;

        if (! $this->editabilityService->dealerCanEdit($invoice, $dealer)) {
            return back()->with('error', 'This invoice can no longer be edited because VAT settings changed since it was created.');
        }

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

    public function destroy(DestroyDealerConfigurationInvoicesRequest $request, Invoice $invoice): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $invoice->delete();

        return back()->with('success', 'Invoice deleted.');
    }

    public function export(ExportDealerConfigurationInvoicesRequest $request, Invoice $invoice): RedirectResponse
    {
        return back()->with('success', 'Export endpoint is prepared. PDF generation will be added next.');
    }
}
