<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\System;

use App\Actions\Backoffice\Shared\Invoices\UpsertInvoiceAction;
use App\Enums\InvoiceCustomerTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Invoices\CreateSystemInvoicesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Invoices\DestroySystemInvoicesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Invoices\EditSystemInvoicesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Invoices\ExportSystemInvoicesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Invoices\IndexSystemInvoicesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Invoices\StoreSystemInvoicesRequest;
use App\Http\Requests\Backoffice\GuardBackoffice\System\Invoices\UpdateSystemInvoicesRequest;
use App\Http\Resources\Backoffice\Shared\Invoices\InvoiceEditResource;
use App\Http\Resources\Backoffice\Shared\Invoices\InvoiceIndexResource;
use App\Models\Invoice\Invoice;
use App\Support\Invoices\InvoiceEditabilityService;
use App\Support\Invoices\InvoiceIndexService;
use App\Support\Invoices\InvoiceSectionOptions;
use App\Support\Invoices\InvoiceVatSnapshotResolver;
use App\Support\Settings\SystemSettingsResolver;
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
        private readonly SystemSettingsResolver $systemSettingsResolver
    ) {
    }

    public function index(IndexSystemInvoicesRequest $request): Response
    {
        $actor = $request->user('backoffice');
        $filters = $request->validated();
        $settings = $this->systemSettingsResolver->resolve(['system_currency']);
        $canCreate = $actor->hasPermissionTo('createSystemInvoices', 'backoffice');
        $canEdit = $actor->hasPermissionTo('editSystemInvoices', 'backoffice');
        $canDelete = $actor->hasPermissionTo('deleteSystemInvoices', 'backoffice');

        $records = $this->indexService->paginate(
            query: Invoice::query()->system(),
            filters: $filters
        );

        $request->attributes->set('invoice_context', [
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_export' => $canEdit,
            'can_show_notes' => true,
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
            'publicTitle' => 'Invoices',
            'context' => [
                'mode' => 'system',
                'showDealerColumn' => false,
            ],
            'records' => $records,
            'filters' => $filters,
            'columns' => $columns,
            'createRoute' => route('backoffice.system.invoices.create'),
            'editRouteName' => 'backoffice.system.invoices.edit',
            'deleteRouteName' => 'backoffice.system.invoices.destroy',
            'exportRouteName' => 'backoffice.system.invoices.export',
            'canCreate' => $canCreate,
            'currencySymbol' => (string) ($settings['system_currency'] ?? 'N$'),
        ]);
    }

    public function create(CreateSystemInvoicesRequest $request): Response
    {
        $vatSnapshot = $this->vatSnapshotResolver->forSystem();
        $settings = $this->systemSettingsResolver->resolve([
            'system_currency',
            'contact_no_prefix',
        ]);

        return Inertia::render('Shared/Invoices/Form', [
            'publicTitle' => 'Invoices',
            'context' => [
                'mode' => 'system',
                'showDealerAssociatedStock' => false,
            ],
            'data' => null,
            'customerTypeOptions' => collect(InvoiceCustomerTypeEnum::cases())
                ->map(fn (InvoiceCustomerTypeEnum $enumCase) => ['label' => Str::headline($enumCase->value), 'value' => $enumCase->value])
                ->values()
                ->all(),
            'sectionOptions' => InvoiceSectionOptions::system(),
            'vat' => $vatSnapshot,
            'canEdit' => true,
            'canDelete' => false,
            'canExport' => false,
            'canShowNotes' => false,
            'indexRoute' => route('backoffice.system.invoices.index'),
            'storeRoute' => route('backoffice.system.invoices.store'),
            'updateRoute' => null,
            'destroyRoute' => null,
            'exportRoute' => null,
            'customerSearchRoute' => route('backoffice.system.invoices.customers.search'),
            'customerStoreRoute' => route('backoffice.system.invoices.customers.store'),
            'lineItemSuggestionRoute' => route('backoffice.system.invoices.line-item-suggestions'),
            'returnTo' => $request->input('return_to', route('backoffice.system.invoices.index')),
            'currencySymbol' => (string) ($settings['system_currency'] ?? 'N$'),
            'contactNoPrefix' => (string) ($settings['contact_no_prefix'] ?? ''),
        ]);
    }

    public function store(StoreSystemInvoicesRequest $request): RedirectResponse
    {
        $vatSnapshot = $this->vatSnapshotResolver->forSystem();
        $actor = $request->user('backoffice');

        $this->upsertInvoiceAction->execute(
            invoice: null,
            data: $request->validated(),
            actor: $actor,
            dealer: null,
            vatSnapshot: $vatSnapshot
        );

        return redirect($request->input('return_to', route('backoffice.system.invoices.index')))
            ->with('success', 'Invoice created.');
    }

    public function edit(EditSystemInvoicesRequest $request, Invoice $invoice): Response|RedirectResponse
    {
        $settings = $this->systemSettingsResolver->resolve([
            'system_currency',
            'contact_no_prefix',
        ]);
        $allowedSections = InvoiceSectionOptions::valuesForSystem();

        $invoice->load([
            'customer',
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

        if (! $this->editabilityService->systemCanEdit($invoice)) {
            return redirect($request->input('return_to', route('backoffice.system.invoices.index')))
                ->with('error', 'This invoice can no longer be edited because VAT settings changed since it was created.');
        }

        return Inertia::render('Shared/Invoices/Form', [
            'publicTitle' => 'Invoices',
            'context' => [
                'mode' => 'system',
                'showDealerAssociatedStock' => true,
            ],
            'data' => (new InvoiceEditResource($invoice))->resolve(),
            'customerTypeOptions' => collect(InvoiceCustomerTypeEnum::cases())
                ->map(fn (InvoiceCustomerTypeEnum $enumCase) => ['label' => Str::headline($enumCase->value), 'value' => $enumCase->value])
                ->values()
                ->all(),
            'sectionOptions' => InvoiceSectionOptions::system(),
            'vat' => [
                'vat_enabled' => (bool) $invoice->vat_enabled,
                'vat_percentage' => $invoice->vat_percentage !== null ? (float) $invoice->vat_percentage : null,
                'vat_number' => $invoice->vat_number,
            ],
            'canEdit' => true,
            'canDelete' => true,
            'canExport' => true,
            'canShowNotes' => true,
            'indexRoute' => route('backoffice.system.invoices.index'),
            'storeRoute' => route('backoffice.system.invoices.store'),
            'updateRoute' => route('backoffice.system.invoices.update', $invoice),
            'destroyRoute' => route('backoffice.system.invoices.destroy', $invoice),
            'exportRoute' => route('backoffice.system.invoices.export', $invoice),
            'customerSearchRoute' => route('backoffice.system.invoices.customers.search'),
            'customerStoreRoute' => route('backoffice.system.invoices.customers.store'),
            'lineItemSuggestionRoute' => route('backoffice.system.invoices.line-item-suggestions'),
            'returnTo' => $request->input('return_to', route('backoffice.system.invoices.index')),
            'currencySymbol' => (string) ($settings['system_currency'] ?? 'N$'),
            'contactNoPrefix' => (string) ($settings['contact_no_prefix'] ?? ''),
        ]);
    }

    public function update(UpdateSystemInvoicesRequest $request, Invoice $invoice): RedirectResponse
    {
        if (! $this->editabilityService->systemCanEdit($invoice)) {
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
            dealer: null,
            vatSnapshot: $vatSnapshot
        );

        return back()->with('success', 'Invoice updated.');
    }

    public function destroy(DestroySystemInvoicesRequest $request, Invoice $invoice): RedirectResponse
    {
        // @todo Revisit destroy behavior and ensure dependent entities are handled per business rules.
        $invoice->delete();

        return back()->with('success', 'Invoice deleted.');
    }

    public function export(ExportSystemInvoicesRequest $request, Invoice $invoice): RedirectResponse
    {
        return back()->with('success', 'Export endpoint is prepared. PDF generation will be added next.');
    }
}
