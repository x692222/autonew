<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice\System;

use App\Actions\Backoffice\Shared\Payments\VerifyPaymentAction;
use App\Http\Controllers\Controller;
use App\Models\Payments\Payment;
use App\Support\Payments\PaymentValidationRules;
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
        private readonly PaymentValidationRules $validationRules,
        private readonly PaymentVerificationsIndexService $indexService,
        private readonly VerifyPaymentAction $verifyPaymentAction,
        private readonly DocumentSettingsPresenter $documentSettings,
    ) {
    }

    public function index(Request $request): Response
    {
        Gate::authorize('indexVerifications', Payment::class);
        $filters = $request->validate($this->validationRules->index());
        $filters['verification_status'] = $filters['verification_status'] ?? 'pending';
        $records = $this->indexService->paginate($filters);

        $records->through(fn (Payment $payment) => $this->indexService->toArray($payment, fn (Payment $record) => [
            'view' => Gate::inspect('view', $record)->allowed(),
            'verify' => Gate::inspect('verify', $record)->allowed() && ! (bool) $record->is_approved,
        ]));

        return Inertia::render('Shared/Payments/VerifyIndex', [
            'publicTitle' => 'Verify Payments',
            'context' => ['mode' => 'system'],
            'records' => $records,
            'filters' => $filters,
            'currencySymbol' => $this->documentSettings->system()['currencySymbol'],
            'verifyRouteName' => 'backoffice.system.verify-payments.verify',
            'paymentShowRouteName' => 'backoffice.system.payments.show',
            'invoiceEditRouteName' => 'backoffice.system.invoices.edit',
        ]);
    }

    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('verify', $payment);
        $this->verifyPaymentAction->execute($payment, $request->user('backoffice'));

        return back()->with('success', 'Payment verified.');
    }
}
