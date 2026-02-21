<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers;

use App\Actions\Backoffice\Shared\Customers\UpsertCustomerAction;
use App\Actions\Backoffice\Shared\Customers\DeleteCustomerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Shared\Customers\IndexCustomersRequest;
use App\Http\Requests\Backoffice\Shared\Customers\UpsertCustomerRequest;
use App\Http\Resources\Backoffice\Shared\Customers\CustomerIndexResource;
use App\Models\Dealer\Dealer;
use App\Models\Payments\Payment;
use App\Models\Quotation\Customer;
use App\Support\Customers\CustomersIndexService;
use App\Support\Customers\CustomerDateFormatter;
use App\Support\Invoices\InvoiceAmountSummaryService;
use App\Support\Options\GeneralOptions;
use App\Support\Settings\DocumentSettingsPresenter;
use App\Support\Tables\DataTableColumnBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CustomersController extends Controller
{
    public function __construct(
        private readonly CustomersIndexService $indexService,
        private readonly UpsertCustomerAction $upsertCustomerAction,
        private readonly InvoiceAmountSummaryService $amountSummaryService,
        private readonly DocumentSettingsPresenter $documentSettings,
        private readonly CustomerDateFormatter $dateFormatter,
    ) {
    }

    public function index(IndexCustomersRequest $request, Dealer $dealer): Response
    {
        Gate::authorize('showCustomers', $dealer);

        $actor = $request->user('backoffice');
        $filters = $request->validated();
        $records = $this->indexService->paginate($filters, $dealer->id);

        $request->attributes->set('customer_context', [
            'can_view' => $actor?->hasPermissionTo('editDealershipCustomers', 'backoffice') ?? false,
            'can_edit' => $actor?->hasPermissionTo('editDealershipCustomers', 'backoffice') ?? false,
            'can_delete' => $actor?->hasPermissionTo('deleteDealershipCustomers', 'backoffice') ?? false,
        ]);

        $records->setCollection(
            $records->getCollection()->map(
                fn (Customer $customer) => (new CustomerIndexResource($customer))->toArray($request)
            )
        );

        $columns = DataTableColumnBuilder::make(
            keys: [
                'type',
                'firstname',
                'lastname',
                'email',
                'contact_number',
                'quotations_count',
                'invoices_count',
                'payments_count',
                'created_at',
            ],
            allSortable: true,
            numericKeys: ['quotations_count', 'invoices_count', 'payments_count']
        );

        return Inertia::render('Shared/Customers/Index', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'pageTab' => 'customers',
            'context' => ['mode' => 'dealer-backoffice'],
            'records' => $records,
            'filters' => $filters,
            'columns' => $columns,
            'createRoute' => route('backoffice.dealer-management.dealers.customers.create', $dealer),
            'showRouteName' => 'backoffice.dealer-management.dealers.customers.show',
            'editRouteName' => 'backoffice.dealer-management.dealers.customers.edit',
            'deleteRouteName' => 'backoffice.dealer-management.dealers.customers.destroy',
            'canCreate' => $actor?->hasPermissionTo('createDealershipCustomers', 'backoffice') ?? false,
        ]);
    }

    public function create(Request $request, Dealer $dealer): Response
    {
        Gate::authorize('createCustomer', $dealer);
        $settings = $this->documentSettings->dealer($dealer->id, includeContactNoPrefix: true);

        return Inertia::render('Shared/Customers/Form', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'pageTab' => 'customers',
            'context' => ['mode' => 'dealer-backoffice'],
            'data' => null,
            'customerTypeOptions' => GeneralOptions::quotationCustomerTypes()->resolve(),
            'indexRoute' => route('backoffice.dealer-management.dealers.customers.index', $dealer),
            'storeRoute' => route('backoffice.dealer-management.dealers.customers.store', $dealer),
            'updateRoute' => null,
            'destroyRoute' => null,
            'showRoute' => null,
            'canEdit' => true,
            'canDelete' => false,
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.customers.index', $dealer)),
            'contactNoPrefix' => $settings['contactNoPrefix'],
        ]);
    }

    public function store(UpsertCustomerRequest $request, Dealer $dealer): RedirectResponse
    {
        Gate::authorize('createCustomer', $dealer);

        $customer = $this->upsertCustomerAction->execute(
            customer: null,
            data: $request->validated(),
            dealer: $dealer
        );

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.customers.index', $dealer)))
            ->with('success', sprintf('Customer %s created.', trim($customer->firstname . ' ' . $customer->lastname)));
    }

    public function show(Request $request, Dealer $dealer, Customer $customer): Response
    {
        Gate::authorize('viewCustomer', [$dealer, $customer]);

        $actor = $request->user('backoffice');
        $canViewInvoices = $actor?->hasPermissionTo('editDealershipInvoices', 'backoffice') ?? false;
        $settings = $this->documentSettings->dealer($dealer->id);
        $invoiceRows = $canViewInvoices
            ? $customer->invoices()
                ->orderByDesc('invoice_date')
                ->withSum('payments as paid_amount', 'amount')
                ->limit(20)
                ->select(['id', 'invoice_identifier', 'invoice_date'])
                ->tap(fn ($query) => $this->amountSummaryService->applyComputedTotalAmount($query))
                ->get()
                ->map(fn ($invoice) => [
                    'invoice_id' => $invoice->id,
                    'invoice_identifier' => (string) $invoice->invoice_identifier,
                    'invoice_date' => optional($invoice->invoice_date)?->format('Y-m-d'),
                    'total_amount' => $invoice->total_amount !== null ? (float) $invoice->total_amount : null,
                    'paid_amount' => $invoice->paid_amount !== null ? (float) $invoice->paid_amount : null,
                    'status' => ((float) ($invoice->paid_amount ?? 0) >= (float) ($invoice->total_amount ?? 0) && (float) ($invoice->total_amount ?? 0) > 0)
                        ? 'FULLY PAID'
                        : ((float) ($invoice->paid_amount ?? 0) > 0 ? 'PARTIAL PAYMENT' : 'NOT PAID'),
                    'url' => route('backoffice.dealer-management.dealers.invoices.edit', ['dealer' => $dealer->id, 'invoice' => $invoice->id, 'return_to' => $request->fullUrl()]),
                ])
                ->values()
                ->all()
            : [];

        $quoteTotal = (float) ($customer->quotations()->sum('total_amount') ?? 0);
        $invoiceTotal = $this->amountSummaryService->sumComputedTotalAmountForInvoices(
            \App\Models\Invoice\Invoice::query()->where('customer_id', $customer->id)
        );
        $invoicePaid = (float) (
            Payment::query()
                ->whereHas('invoice', fn ($query) => $query->where('customer_id', $customer->id))
                ->sum('amount') ?? 0
        );
        $outstanding = max(0, $invoiceTotal - $invoicePaid);

        return Inertia::render('Shared/Customers/Show', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'pageTab' => 'customers',
            'context' => ['mode' => 'dealer-backoffice'],
            'data' => [
                'id' => $customer->id,
                'type' => $customer->type?->value ?? (string) $customer->type,
                'title' => $customer->title,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'id_number' => $customer->id_number,
                'email' => $customer->email,
                'contact_number' => $customer->contact_number,
                'address' => $customer->address,
                'vat_number' => $customer->vat_number,
                'created_at' => optional($customer->created_at)?->format('Y-m-d'),
            ],
            'summary' => [
                'total_quotes' => (int) $customer->quotations()->count(),
                'total_invoices' => (int) $customer->invoices()->count(),
                'total_quote_value' => $quoteTotal,
                'total_invoice_value' => $invoiceTotal,
                'total_outstanding' => $outstanding,
                'last_quotation_date' => $this->dateFormatter->format($customer->quotations()->max('quotation_date')),
                'last_invoice_date' => $this->dateFormatter->format($customer->invoices()->max('invoice_date')),
                'last_payment_date' => $this->dateFormatter->format(
                    Payment::query()
                        ->whereHas('invoice', fn ($query) => $query->where('customer_id', $customer->id))
                        ->max('payment_date')
                ),
            ],
            'associatedInvoices' => $invoiceRows,
            'canViewAssociatedInvoices' => $canViewInvoices,
            'currencySymbol' => $settings['currencySymbol'],
            'editRoute' => route('backoffice.dealer-management.dealers.customers.edit', ['dealer' => $dealer->id, 'customer' => $customer->id, 'return_to' => $request->fullUrl()]),
            'indexRoute' => route('backoffice.dealer-management.dealers.customers.index', $dealer),
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.customers.index', $dealer)),
        ]);
    }

    public function edit(Request $request, Dealer $dealer, Customer $customer): Response
    {
        Gate::authorize('editCustomer', [$dealer, $customer]);
        $settings = $this->documentSettings->dealer($dealer->id, includeContactNoPrefix: true);

        return Inertia::render('Shared/Customers/Form', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'pageTab' => 'customers',
            'context' => ['mode' => 'dealer-backoffice'],
            'data' => [
                'id' => $customer->id,
                'type' => $customer->type?->value ?? (string) $customer->type,
                'title' => $customer->title,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'id_number' => $customer->id_number,
                'email' => $customer->email,
                'contact_number' => $customer->contact_number,
                'address' => $customer->address,
                'vat_number' => $customer->vat_number,
            ],
            'customerTypeOptions' => GeneralOptions::quotationCustomerTypes()->resolve(),
            'indexRoute' => route('backoffice.dealer-management.dealers.customers.index', $dealer),
            'storeRoute' => route('backoffice.dealer-management.dealers.customers.store', $dealer),
            'updateRoute' => route('backoffice.dealer-management.dealers.customers.update', [$dealer, $customer]),
            'destroyRoute' => route('backoffice.dealer-management.dealers.customers.destroy', [$dealer, $customer]),
            'showRoute' => route('backoffice.dealer-management.dealers.customers.show', ['dealer' => $dealer->id, 'customer' => $customer->id, 'return_to' => $request->input('return_to', route('backoffice.dealer-management.dealers.customers.index', $dealer))]),
            'canEdit' => true,
            'canDelete' => Gate::inspect('deleteCustomer', [$dealer, $customer])->allowed(),
            'returnTo' => $request->input('return_to', route('backoffice.dealer-management.dealers.customers.index', $dealer)),
            'contactNoPrefix' => $settings['contactNoPrefix'],
        ]);
    }

    public function update(UpsertCustomerRequest $request, Dealer $dealer, Customer $customer): RedirectResponse
    {
        Gate::authorize('editCustomer', [$dealer, $customer]);

        $this->upsertCustomerAction->execute(
            customer: $customer,
            data: $request->validated(),
            dealer: $dealer
        );

        return back()->with('success', 'Customer updated.');
    }

    public function destroy(Request $request, Dealer $dealer, Customer $customer, DeleteCustomerAction $action): RedirectResponse
    {
        Gate::authorize('deleteCustomer', [$dealer, $customer]);
        $action->execute($customer, $dealer);

        return redirect($request->input('return_to', route('backoffice.dealer-management.dealers.customers.index', $dealer)))
            ->with('success', 'Customer deleted.');
    }
}
