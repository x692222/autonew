<?php

namespace App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration;

use App\Actions\Backoffice\Shared\Payments\DeletePaymentAction;
use App\Actions\Backoffice\Shared\Payments\UpsertPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Shared\Payments\IndexPaymentsRequest;
use App\Http\Requests\Backoffice\Shared\Payments\UpsertPaymentRequest;
use App\Models\Invoice\Invoice;
use App\Models\Payments\Payment;
use App\Support\Payments\InvoicePaymentSummaryService;
use App\Support\Payments\PaymentsIndexService;
use App\Support\Stock\AssociatedStockPresenter;
use App\Support\Invoices\InvoiceAmountSummaryService;
use App\Support\Options\BankingDetailOptions;
use App\Support\Options\GeneralOptions;
use App\Support\Settings\DocumentSettingsPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PaymentsController extends Controller
{
    public function __construct(
        private readonly PaymentsIndexService $indexService,
        private readonly InvoicePaymentSummaryService $paymentSummaryService,
        private readonly InvoiceAmountSummaryService $amountSummaryService,
        private readonly DocumentSettingsPresenter $documentSettings,
    ) {
    }

    public function index(IndexPaymentsRequest $request): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexPayments', $dealer);

        $filters = $request->validated();

        $records = $this->indexService->paginate($filters, $dealer->id);

        $records->through(fn (Payment $payment) => $this->indexService->toArray($payment, fn (Payment $record) => [
                'view' => Gate::forUser($actor)->inspect('dealerConfigurationViewPayment', $record)->allowed(),
                'edit' => ! (bool) $record->is_approved && Gate::forUser($actor)->inspect('dealerConfigurationEditPayment', $record)->allowed(),
                'delete' => ! (bool) $record->is_approved && Gate::forUser($actor)->inspect('dealerConfigurationDeletePayment', $record)->allowed(),
            ]));

        return Inertia::render('Shared/Payments/Index', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'context' => ['mode' => 'dealer'],
            'records' => $records,
            'filters' => $filters,
            'canCreate' => false,
            'bankingDetailOptions' => BankingDetailOptions::forDealer((string) $dealer->id)->resolve(),
            'verificationStatusOptions' => GeneralOptions::paymentVerificationStatuses()->resolve(),
        ]);
    }

    public function show(Request $request, Payment $payment): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationViewPayment', $payment);
        $canViewAssociatedInvoices = $actor->hasPermissionTo('editDealershipInvoices', 'dealer');
        $settings = $this->documentSettings->dealer($dealer->id);

        $payment->load([
            'invoice:id,invoice_identifier,invoice_date,is_fully_paid,dealer_id',
            'bankingDetail:id,bank,account_number',
            'createdBy',
            'verifications.verifiedBy',
            'invoice.lineItems.stock',
            'invoice.lineItems.stock.vehicleItem.make',
            'invoice.lineItems.stock.vehicleItem.model',
            'invoice.lineItems.stock.commercialItem.make',
            'invoice.lineItems.stock.commercialItem.model',
            'invoice.lineItems.stock.motorbikeItem.make',
            'invoice.lineItems.stock.motorbikeItem.model',
        ]);

        $associatedStock = $payment->invoice?->lineItems
            ?->pluck('stock')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($stock) => app(AssociatedStockPresenter::class)->present($stock))
            ->all() ?? [];

        $invoiceTotalPaid = $payment->invoice ? $this->paymentSummaryService->totalPaid($payment->invoice) : 0;
        $invoiceIsFullyPaid = $payment->invoice ? $this->paymentSummaryService->isFullyPaid($payment->invoice) : false;
        $invoiceTotalPayments = $payment->invoice ? (int) $payment->invoice->payments()->count() : 0;
        $invoiceVerifiedPayments = $payment->invoice ? (int) $payment->invoice->payments()->where('is_approved', true)->count() : 0;
        $invoiceIsFullyVerified = $invoiceTotalPayments > 0 && $invoiceTotalPayments === $invoiceVerifiedPayments;
        $invoiceTotalAmount = $payment->invoice ? $this->amountSummaryService->totalForInvoice($payment->invoice) : null;

        $associatedInvoices = $payment->invoice ? [[
            'invoice_id' => $payment->invoice->id,
            'invoice_identifier' => (string) ($payment->invoice->invoice_identifier ?? '-'),
            'invoice_date' => optional($payment->invoice->invoice_date)?->format('Y-m-d'),
            'total_amount' => $invoiceTotalAmount !== null ? (float) $invoiceTotalAmount : null,
            'paid_amount' => $invoiceTotalPaid,
            'is_fully_paid' => $invoiceIsFullyPaid,
            'is_fully_verified' => $invoiceIsFullyVerified,
            'status' => $invoiceIsFullyPaid
                ? 'FULLY PAID'
                : ($invoiceTotalPaid > 0 ? 'PARTIAL PAYMENT' : 'NOT PAID'),
            'url' => route('backoffice.dealer-configuration.invoices.edit', ['invoice' => $payment->invoice->id, 'return_to' => $request->fullUrl()]),
        ]] : [];

        return Inertia::render('Shared/Payments/Show', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'context' => ['mode' => 'dealer'],
            'payment' => [
                'id' => $payment->id,
                'invoice_id' => $payment->invoice?->id,
                'invoice_identifier' => $payment->invoice?->invoice_identifier,
                'invoice_date' => optional($payment->invoice?->invoice_date)?->format('Y-m-d'),
                'description' => $payment->description,
                'amount' => $payment->amount !== null ? (float) $payment->amount : null,
                'payment_date' => optional($payment->payment_date)?->format('Y-m-d'),
                'payment_method' => $payment->payment_method?->value ?? (string) $payment->payment_method,
                'banking_detail_id' => $payment->banking_detail_id,
                'banking_detail_bank_account' => trim((string) (($payment->bankingDetail?->bank ?? '') . ' ' . ($payment->bankingDetail?->account_number ?? ''))),
                'recorded_by' => $payment->recordedByLabel(),
                'recorded_ip' => $payment->created_from_ip,
                'is_approved' => (bool) $payment->is_approved,
            ],
            'verifications' => $payment->verifications
                ->sortByDesc('date_verified')
                ->map(fn ($verification) => [
                    'id' => $verification->id,
                    'verified_at' => optional($verification->date_verified)?->format('Y-m-d H:i:s'),
                    'verified_by' => $verification->verifiedByLabel(),
                    'verified_by_guard' => $verification->verifiedByGuardLabel(),
                    'amount_verified' => $verification->amount_verified !== null ? (float) $verification->amount_verified : null,
                ])->values()->all(),
            'associatedStock' => $associatedStock,
            'associatedInvoices' => $associatedInvoices,
            'canViewAssociatedInvoices' => $canViewAssociatedInvoices,
            'currencySymbol' => $settings['currencySymbol'],
            'bankingDetailOptions' => BankingDetailOptions::forDealer((string) $dealer->id)->resolve(),
            'canEdit' => Gate::forUser($actor)->inspect('dealerConfigurationEditPayment', $payment)->allowed() && ! (bool) $payment->is_approved,
            'updateRoute' => route('backoffice.dealer-configuration.payments.update', ['payment' => $payment->id]),
            'canVerify' => Gate::forUser($actor)->inspect('dealerConfigurationVerifyPayment', $payment)->allowed() && ! (bool) $payment->is_approved,
            'verifyUrl' => route('backoffice.dealer-configuration.verify-payments.verify', ['payment' => $payment->id]),
            'returnTo' => $request->input('return_to', route('backoffice.dealer-configuration.payments.index')),
        ]);
    }

    public function storeForInvoice(
        UpsertPaymentRequest $request,
        Invoice $invoice,
        UpsertPaymentAction $upsertPaymentAction
    ): RedirectResponse
    {
        $actor = $request->user('dealer');
        Gate::forUser($actor)->authorize('dealerConfigurationEditInvoice', $invoice);
        if ($this->paymentSummaryService->isFullyPaid($invoice)) {
            return back()->with('error', 'This invoice is fully paid and cannot accept additional payments.');
        }
        $data = $request->validated();
        $upsertPaymentAction->execute(null, $invoice, $data, $actor, $request->ip());

        return back()->with('success', 'Payment recorded.');
    }

    public function update(
        UpsertPaymentRequest $request,
        Payment $payment,
        UpsertPaymentAction $upsertPaymentAction
    ): RedirectResponse
    {
        $actor = $request->user('dealer');
        if ((bool) $payment->is_approved) {
            return back()->with('error', 'Verified payments cannot be edited.');
        }
        Gate::forUser($actor)->authorize('dealerConfigurationEditPayment', $payment);
        $data = $request->validated();
        $upsertPaymentAction->execute($payment, $payment->invoice, $data, $actor, $request->ip());

        return back()->with('success', 'Payment updated.');
    }

    public function destroy(Request $request, Payment $payment, DeletePaymentAction $deletePaymentAction): RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        if ((bool) $payment->is_approved) {
            return back()->with('error', 'Verified payments cannot be deleted.');
        }
        Gate::forUser($actor)->authorize('dealerConfigurationDeletePayment', $payment);
        $deletePaymentAction->execute($payment, $dealer);

        return back()->with('success', 'Payment deleted.');
    }
}
