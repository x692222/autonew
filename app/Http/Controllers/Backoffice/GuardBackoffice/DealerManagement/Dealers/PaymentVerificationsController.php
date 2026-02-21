<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\DealerManagement\Dealers;

use App\Actions\Backoffice\Shared\Payments\VerifyPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Shared\Payments\IndexPaymentVerificationsRequest;
use App\Models\Dealer\Dealer;
use App\Models\Payments\Payment;
use App\Support\Payments\PaymentVerificationsIndexService;
use App\Support\Settings\DocumentSettingsPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PaymentVerificationsController extends Controller
{
    public function __construct(
        private readonly PaymentVerificationsIndexService $indexService,
        private readonly VerifyPaymentAction $verifyPaymentAction,
        private readonly DocumentSettingsPresenter $documentSettings,
    ) {
    }

    public function index(IndexPaymentVerificationsRequest $request, Dealer $dealer): Response
    {
        Gate::authorize('showPaymentVerifications', $dealer);
        $filters = $request->validated();
        $filters['verification_status'] = $filters['verification_status'] ?? 'pending';
        $records = $this->indexService->paginate($filters, $dealer->id);

        $records->through(fn (Payment $payment) => $this->indexService->toArray($payment, fn (Payment $record) => [
            'view' => Gate::inspect('viewPayment', [$dealer, $record])->allowed(),
            'verify' => Gate::inspect('verifyPayment', [$dealer, $record])->allowed() && ! (bool) $record->is_approved,
        ]));

        return Inertia::render('Shared/Payments/VerifyIndex', [
            'publicTitle' => 'Dealer Management',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'pageTab' => 'verify-payments',
            'context' => ['mode' => 'dealer-backoffice'],
            'records' => $records,
            'filters' => $filters,
            'currencySymbol' => $this->documentSettings->dealer($dealer->id)['currencySymbol'],
            'verifyRouteName' => 'backoffice.dealer-management.dealers.verify-payments.verify',
            'paymentShowRouteName' => 'backoffice.dealer-management.dealers.payments.show',
            'invoiceEditRouteName' => 'backoffice.dealer-management.dealers.invoices.edit',
        ]);
    }

    public function verify(Request $request, Dealer $dealer, Payment $payment): RedirectResponse
    {
        Gate::authorize('verifyPayment', [$dealer, $payment]);
        $this->verifyPaymentAction->execute($payment, $request->user('backoffice'), $dealer);

        return back()->with('success', 'Payment verified.');
    }
}
