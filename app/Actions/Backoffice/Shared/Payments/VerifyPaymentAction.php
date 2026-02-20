<?php

namespace App\Actions\Backoffice\Shared\Payments;

use App\Models\Payments\Payment;
use App\Models\Payments\PaymentVerification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerifyPaymentAction
{
    public function execute(Payment $payment, Model $actor): PaymentVerification
    {
        if ((bool) $payment->is_approved || $payment->verifications()->exists()) {
            throw ValidationException::withMessages([
                'payment' => ['This payment has already been verified.'],
            ]);
        }

        return DB::transaction(function () use ($payment, $actor): PaymentVerification {
            $verification = PaymentVerification::query()->create([
                'payment_id' => $payment->id,
                'amount_verified' => (float) ($payment->amount ?? 0),
                'date_verified' => now(),
                'verified_by_type' => get_class($actor),
                'verified_by_id' => (string) $actor->getKey(),
            ]);

            $payment->update(['is_approved' => true]);

            return $verification;
        });
    }
}
