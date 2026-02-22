<?php

namespace App\Actions\Backoffice\Shared\Payments;

use App\Models\Dealer\Dealer;
use App\Models\Payments\Payment;
use App\Support\Payments\InvoicePaymentStateUpdater;
use App\Support\Security\TenantScopeEnforcer;
use Illuminate\Validation\ValidationException;

class DeletePaymentAction
{
    public function __construct(
        private readonly InvoicePaymentStateUpdater $paymentStateUpdater,
        private readonly TenantScopeEnforcer $tenantScopeEnforcer
    )
    {
    }

    public function execute(Payment $payment, ?Dealer $dealer = null): void
    {
        $this->tenantScopeEnforcer->assertSameDealerScope($payment->dealer_id, $dealer?->id, 'payment');
        if ((bool) $payment->is_approved) {
            throw ValidationException::withMessages([
                'payment' => ['Verified payments cannot be deleted.'],
            ]);
        }
        $invoice = $payment->invoice()->first();
        $wasFullyPaid = (bool) ($invoice?->is_fully_paid ?? false);
        $payment->delete();
        $this->paymentStateUpdater->handleDeletedPayment($invoice, $wasFullyPaid);
    }
}
