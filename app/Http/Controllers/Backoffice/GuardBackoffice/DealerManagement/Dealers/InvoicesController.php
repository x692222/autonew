<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers;

use App\Actions\Backoffice\Shared\Invoices\UpsertInvoiceAction;
use App\Enums\PaymentMethodEnum;
use App\Models\Billing\BankingDetail;
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
use App\Support\Settings\DocumentSettingsPresenter;
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
        private readonly DocumentSettingsPresenter $documentSettings
    ) {
    }

    public function index(IndexDealerInvoicesRequest $request, Dealer $dealer): Response
    {
        $actor = $request->user('backoffice');
        $filters = $request->validated();
        $documentSettings = $this->documentSettings->dealer($dealer->id);
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
            'is_fully_paid',
            'invoice_identifier',
            'total_items_general_accessories',
            'payable_by',
            'customer_firstname',
            'customer_lastname',
            'total_amount',
            'total_paid_amount',
            'total_due',
        ])->map(fn (string $key) => [
            'name' => $key,
            'label' => Str::headline($key),
            'sortable' => true,
            'align' => in_array($key, ['total_items_general_accessories', 'total_amount', 'total_paid_amount', 'total_due'], true) ? 'right' : 'left',
            'field' => $key,
            'numeric' => in_array($key, ['total_items_general_accessories', 'total_amount', 'total_paid_amount', 'total_due'], true),
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
            'currencySymbol' => $documentSettings['currencySymbol'],
        ]);
    }

    public function create(CreateDealerInvoicesRequest $request, Dealer $dealer): Response
    {
        $vatSnapshot = $this->vatSnapshotResolver->forDealer($dealer);
        $documentSettings = $this->documentSettings->dealer($dealer->id, includeContactNoPrefix: true);
        $canCreateCustomer = $request->user('backoffice')?->hasPermissionTo('createDealershipCustomers', 'backoffice') ?? false;

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
            'canCreateCustomer' => $canCreateCustomer,
            'indexRoute' => route('backoffice.dealer-management.dealers.invoices.index', $dealer),
            'storeRoute' => route('backoffice.dealer-management.dealers.invoices.store', $dealer),
            'updateRoute' => null,
            'destroyRoute' => null,
            'exportRoute' => null,
            'customerSearchRoute' => route('backoffice.dealer-management.dealers.invoices.customers.search', $dealer),
            'customerStoreRoute' => route('backoffice.dealer-management.dealers.invoices.customers.store', $dealer),
            'lineItemSuggestionRoute' => route('backoffice.dealer-management.dealers.invoices.line-item-suggestions', $dealer),
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.invoices.index', $dealer)),
            'currencySymbol' => $documentSettings['currencySymbol'],
            'contactNoPrefix' => $documentSettings['contactNoPrefix'],
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
        $documentSettings = $this->documentSettings->dealer($dealer->id, includeContactNoPrefix: true);
        $canEditInvoice = $this->editabilityService->dealerCanEdit($invoice, $dealer);
        $canRecordPayment = $this->editabilityService->canRecordPayment($invoice);

        $invoice->load([
            'customer',
            'payments.bankingDetail',
            'payments.createdBy',
            'lineItems.stock',
            'lineItems.stock.vehicleItem.make',
            'lineItems.stock.vehicleItem.model',
            'lineItems.stock.commercialItem.make',
            'lineItems.stock.commercialItem.model',
            'lineItems.stock.motorbikeItem.make',
            'lineItems.stock.motorbikeItem.model',
        ]);

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
            'canEdit' => $canEditInvoice,
            'canDelete' => true,
            'canExport' => true,
            'canShowNotes' => true,
            'canCreateCustomer' => $request->user('backoffice')?->hasPermissionTo('createDealershipCustomers', 'backoffice') ?? false,
            'payments' => $invoice->payments
                ->sortByDesc('payment_date')
                ->map(fn ($payment) => [
                    'id' => $payment->id,
                    'description' => $payment->description,
                    'amount' => $payment->amount !== null ? (float) $payment->amount : null,
                    'payment_date' => optional($payment->payment_date)?->format('Y-m-d'),
                    'payment_method' => $payment->payment_method?->value ?? (string) $payment->payment_method,
                    'banking_detail_id' => $payment->banking_detail_id,
                    'banking_detail_label' => $payment->bankingDetail?->label,
                    'is_approved' => (bool) $payment->is_approved,
                    'recorded_by' => $payment->recordedByLabel(),
                    'recorded_ip' => $payment->created_from_ip,
                ])
                ->values()
                ->all(),
            'bankingDetailOptions' => BankingDetail::query()
                ->forDealer($dealer->id)
                ->select(['id as value', 'label', 'institution'])
                ->orderBy('label')
                ->get()
                ->map(fn ($row) => ['value' => $row->value, 'label' => trim((string) $row->label . (filled($row->institution) ? ' (' . $row->institution . ')' : ''))])
                ->values()
                ->all(),
            'paymentMethodOptions' => collect(PaymentMethodEnum::cases())
                ->map(fn (PaymentMethodEnum $method) => ['value' => $method->value, 'label' => str($method->value)->replace('_', ' ')->upper()->toString()])
                ->values()
                ->all(),
            'paymentRoutes' => [
                'store' => route('backoffice.dealer-management.dealers.invoices.payments.store', [$dealer, $invoice]),
                'showName' => 'backoffice.dealer-management.dealers.payments.show',
                'updateName' => 'backoffice.dealer-management.dealers.payments.update',
                'deleteName' => 'backoffice.dealer-management.dealers.payments.destroy',
            ],
            'canRecordPayment' => $canRecordPayment,
            'indexRoute' => route('backoffice.dealer-management.dealers.invoices.index', $dealer),
            'storeRoute' => route('backoffice.dealer-management.dealers.invoices.store', $dealer),
            'updateRoute' => route('backoffice.dealer-management.dealers.invoices.update', [$dealer, $invoice]),
            'destroyRoute' => route('backoffice.dealer-management.dealers.invoices.destroy', [$dealer, $invoice]),
            'exportRoute' => route('backoffice.dealer-management.dealers.invoices.export', [$dealer, $invoice]),
            'customerSearchRoute' => route('backoffice.dealer-management.dealers.invoices.customers.search', $dealer),
            'customerStoreRoute' => route('backoffice.dealer-management.dealers.invoices.customers.store', $dealer),
            'lineItemSuggestionRoute' => route('backoffice.dealer-management.dealers.invoices.line-item-suggestions', $dealer),
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.invoices.index', $dealer)),
            'currencySymbol' => $documentSettings['currencySymbol'],
            'contactNoPrefix' => $documentSettings['contactNoPrefix'],
        ]);
    }

    public function update(UpdateDealerInvoicesRequest $request, Dealer $dealer, Invoice $invoice): RedirectResponse
    {
        if (! $this->editabilityService->dealerCanEdit($invoice, $dealer)) {
            return back()->with('error', 'This invoice can no longer be edited due to current VAT/payment edit settings.');
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
