<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\System;

use App\Actions\Backoffice\Shared\Customers\UpsertCustomerAction;
use App\Actions\Backoffice\Shared\Customers\DeleteCustomerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Shared\Customers\IndexCustomersRequest;
use App\Http\Requests\Backoffice\Shared\Customers\UpsertCustomerRequest;
use App\Http\Resources\Backoffice\Shared\Customers\CustomerIndexResource;
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

    public function index(IndexCustomersRequest $request): Response
    {
        Gate::authorize('viewAny', Customer::class);

        $actor = $request->user('backoffice');
        $filters = $request->validated();
        $records = $this->indexService->paginate($filters);

        $request->attributes->set('customer_context', [
            'can_view' => $actor?->hasPermissionTo('editSystemCustomers', 'backoffice') ?? false,
            'can_edit' => $actor?->hasPermissionTo('editSystemCustomers', 'backoffice') ?? false,
            'can_delete' => $actor?->hasPermissionTo('deleteSystemCustomers', 'backoffice') ?? false,
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
            'publicTitle' => 'Customers',
            'context' => ['mode' => 'system'],
            'records' => $records,
            'filters' => $filters,
            'columns' => $columns,
            'createRoute' => route('backoffice.system.customers.create'),
            'showRouteName' => 'backoffice.system.customers.show',
            'editRouteName' => 'backoffice.system.customers.edit',
            'deleteRouteName' => 'backoffice.system.customers.destroy',
            'canCreate' => $actor?->hasPermissionTo('createSystemCustomers', 'backoffice') ?? false,
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Customer::class);
        $settings = $this->documentSettings->system(includeContactNoPrefix: true);

        return Inertia::render('Shared/Customers/Form', [
            'publicTitle' => 'Customers',
            'context' => ['mode' => 'system'],
            'data' => null,
            'customerTypeOptions' => GeneralOptions::quotationCustomerTypes()->resolve(),
            'indexRoute' => route('backoffice.system.customers.index'),
            'storeRoute' => route('backoffice.system.customers.store'),
            'updateRoute' => null,
            'destroyRoute' => null,
            'showRoute' => null,
            'canEdit' => true,
            'canDelete' => false,
            'returnTo' => $request->input('return_to', route('backoffice.system.customers.index')),
            'contactNoPrefix' => $settings['contactNoPrefix'],
        ]);
    }

    public function store(UpsertCustomerRequest $request): RedirectResponse
    {
        Gate::authorize('create', Customer::class);

        $customer = $this->upsertCustomerAction->execute(
            customer: null,
            data: $request->validated(),
            dealer: null
        );

        return redirect($request->input('return_to', route('backoffice.system.customers.index')))
            ->with('success', sprintf('Customer %s created.', trim($customer->firstname . ' ' . $customer->lastname)));
    }

    public function show(Request $request, Customer $customer): Response
    {
        Gate::authorize('view', $customer);

        $actor = $request->user('backoffice');
        $canViewQuotations = $actor?->hasPermissionTo('editSystemQuotations', 'backoffice') ?? false;
        $canViewInvoices = $actor?->hasPermissionTo('editSystemInvoices', 'backoffice') ?? false;
        $canViewPayments = $actor?->hasPermissionTo('viewSystemPayments', 'backoffice') ?? false;
        $settings = $this->documentSettings->system();
        $quotationRows = $canViewQuotations
            ? $customer->quotations()
                ->orderByDesc('quotation_date')
                ->limit(20)
                ->select(['id', 'quote_identifier', 'quotation_date', 'valid_until', 'total_amount'])
                ->get()
                ->map(fn ($quotation) => [
                    'quotation_id' => $quotation->id,
                    'quote_identifier' => (string) $quotation->quote_identifier,
                    'quotation_date' => optional($quotation->quotation_date)?->format('Y-m-d'),
                    'valid_until' => optional($quotation->valid_until)?->format('Y-m-d'),
                    'total_amount' => $quotation->total_amount !== null ? (float) $quotation->total_amount : null,
                    'status' => $quotation->valid_until && $quotation->valid_until->isPast() ? 'EXPIRED' : 'ACTIVE',
                    'url' => route('backoffice.system.quotations.edit', ['quotation' => $quotation->id, 'return_to' => $request->fullUrl()]),
                ])
                ->values()
                ->all()
            : [];
        $invoiceRows = $canViewInvoices
            ? $customer->invoices()
                ->orderByDesc('invoice_date')
                ->limit(20)
                ->select(['id', 'invoice_identifier', 'invoice_date', 'is_fully_paid'])
                ->withSum('payments as paid_amount', 'amount')
                ->withCount([
                    'payments as total_payments_count',
                    'payments as verified_payments_count' => fn ($payments) => $payments->where('is_approved', true),
                ])
                ->tap(fn ($query) => $this->amountSummaryService->applyComputedTotalAmount($query))
                ->get()
                ->map(fn ($invoice) => [
                    'is_fully_verified' => (int) ($invoice->total_payments_count ?? 0) > 0
                        && (int) ($invoice->total_payments_count ?? 0) === (int) ($invoice->verified_payments_count ?? 0),
                    'invoice_id' => $invoice->id,
                    'invoice_identifier' => (string) $invoice->invoice_identifier,
                    'invoice_date' => optional($invoice->invoice_date)?->format('Y-m-d'),
                    'total_amount' => $invoice->total_amount !== null ? (float) $invoice->total_amount : null,
                    'paid_amount' => $invoice->paid_amount !== null ? (float) $invoice->paid_amount : null,
                    'is_fully_paid' => (bool) $invoice->is_fully_paid,
                    'status' => ((float) ($invoice->paid_amount ?? 0) >= (float) ($invoice->total_amount ?? 0) && (float) ($invoice->total_amount ?? 0) > 0)
                        ? 'FULLY PAID'
                        : ((float) ($invoice->paid_amount ?? 0) > 0 ? 'PARTIAL PAYMENT' : 'NOT PAID'),
                    'url' => route('backoffice.system.invoices.edit', ['invoice' => $invoice->id, 'return_to' => $request->fullUrl()]),
                ])
                ->values()
                ->all()
            : [];
        $paymentRows = $canViewPayments
            ? Payment::query()
                ->whereHas('invoice', fn ($query) => $query->where('customer_id', $customer->id))
                ->with('invoice:id,invoice_identifier')
                ->orderByDesc('payment_date')
                ->orderByDesc('id')
                ->limit(20)
                ->get(['id', 'invoice_id', 'payment_date', 'payment_method', 'amount', 'is_approved'])
                ->map(fn ($payment) => [
                    'payment_id' => $payment->id,
                    'invoice_identifier' => (string) ($payment->invoice?->invoice_identifier ?? '-'),
                    'payment_date' => optional($payment->payment_date)?->format('Y-m-d'),
                    'payment_method' => $payment->payment_method?->value ?? (string) $payment->payment_method,
                    'amount' => $payment->amount !== null ? (float) $payment->amount : null,
                    'status' => (bool) $payment->is_approved ? 'APPROVED' : 'NOT APPROVED',
                    'url' => route('backoffice.system.payments.show', ['payment' => $payment->id, 'return_to' => $request->fullUrl()]),
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
            'publicTitle' => 'Customers',
            'context' => ['mode' => 'system'],
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
            'associatedQuotations' => $quotationRows,
            'canViewAssociatedQuotations' => $canViewQuotations,
            'canViewAssociatedInvoices' => $canViewInvoices,
            'associatedPayments' => $paymentRows,
            'canViewAssociatedPayments' => $canViewPayments,
            'currencySymbol' => $settings['currencySymbol'],
            'editRoute' => route('backoffice.system.customers.edit', ['customer' => $customer->id, 'return_to' => $request->fullUrl()]),
            'indexRoute' => route('backoffice.system.customers.index'),
            'returnTo' => $request->input('return_to', route('backoffice.system.customers.index')),
        ]);
    }

    public function edit(Request $request, Customer $customer): Response
    {
        Gate::authorize('update', $customer);
        $settings = $this->documentSettings->system(includeContactNoPrefix: true);

        return Inertia::render('Shared/Customers/Form', [
            'publicTitle' => 'Customers',
            'context' => ['mode' => 'system'],
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
            'indexRoute' => route('backoffice.system.customers.index'),
            'storeRoute' => route('backoffice.system.customers.store'),
            'updateRoute' => route('backoffice.system.customers.update', $customer),
            'destroyRoute' => route('backoffice.system.customers.destroy', $customer),
            'showRoute' => route('backoffice.system.customers.show', ['customer' => $customer->id, 'return_to' => $request->input('return_to', route('backoffice.system.customers.index'))]),
            'canEdit' => true,
            'canDelete' => Gate::inspect('delete', $customer)->allowed(),
            'returnTo' => $request->input('return_to', route('backoffice.system.customers.index')),
            'contactNoPrefix' => $settings['contactNoPrefix'],
        ]);
    }

    public function update(UpsertCustomerRequest $request, Customer $customer): RedirectResponse
    {
        Gate::authorize('update', $customer);

        $this->upsertCustomerAction->execute(
            customer: $customer,
            data: $request->validated(),
            dealer: null
        );

        return redirect($request->input('return_to', route('backoffice.system.customers.index')))
            ->with('success', 'Customer updated.');
    }

    public function destroy(Request $request, Customer $customer, DeleteCustomerAction $action): RedirectResponse
    {
        Gate::authorize('delete', $customer);
        $action->execute($customer);

        return redirect($request->input('return_to', route('backoffice.system.customers.index')))
            ->with('success', 'Customer deleted.');
    }
}
