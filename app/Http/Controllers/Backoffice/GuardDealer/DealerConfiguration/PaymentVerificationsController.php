<?php

namespace App\Http\Controllers\Backoffice\GuardDealer\DealerConfiguration;

use App\Actions\Backoffice\Shared\Payments\VerifyPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Shared\Payments\IndexPaymentVerificationsRequest;
use App\Models\Payments\Payment;
use App\Support\Options\BankingDetailOptions;
use App\Support\Payments\PaymentVerificationAutoRefreshIntervalResolver;
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
        private readonly PaymentVerificationAutoRefreshIntervalResolver $autoRefreshIntervalResolver,
    ) {
    }

    public function index(IndexPaymentVerificationsRequest $request): Response
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationIndexPaymentVerifications', $dealer);

        $filters = $request->validated();
        $filters['verification_status'] = $filters['verification_status'] ?? 'pending';
        $records = $this->indexService->paginate($filters, $dealer->id);

        $records->through(fn (Payment $payment) => $this->indexService->toArray($payment, fn (Payment $record) => [
            'view' => Gate::forUser($actor)->inspect('dealerConfigurationViewPayment', $record)->allowed(),
            'verify' => Gate::forUser($actor)->inspect('dealerConfigurationVerifyPayment', $record)->allowed() && ! (bool) $record->is_approved,
        ]));

        return Inertia::render('Shared/Payments/VerifyIndex', [
            'publicTitle' => 'Configuration',
            'dealer' => ['id' => $dealer->id, 'name' => $dealer->name],
            'context' => ['mode' => 'dealer'],
            'records' => $records,
            'filters' => $filters,
            'currencySymbol' => $this->documentSettings->dealer($dealer->id)['currencySymbol'],
            'bankingDetailOptions' => BankingDetailOptions::forDealer((string) $dealer->id)->resolve(),
            'autoRefreshSeconds' => $this->autoRefreshIntervalResolver->resolve(),
            'verifyRouteName' => 'backoffice.dealer-configuration.verify-payments.verify',
            'paymentShowRouteName' => 'backoffice.dealer-configuration.payments.show',
            'invoiceEditRouteName' => 'backoffice.dealer-configuration.invoices.edit',
        ]);
    }

    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        $actor = $request->user('dealer');
        $dealer = $actor->dealer;
        Gate::forUser($actor)->authorize('dealerConfigurationVerifyPayment', $payment);
        $this->verifyPaymentAction->execute($payment, $actor, $dealer);

        return back()->with('success', 'Payment verified.');
    }
}
