<?php

namespace App\Http\Requests\Backoffice\Shared\Payments;

use App\Models\Dealer\Dealer;
use App\Support\Validation\Payments\PaymentValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpsertPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $dealerId = null;
        $dealer = $this->route('dealer');

        if ($dealer instanceof Dealer) {
            $dealerId = (string) $dealer->id;
        } elseif ($this->user('dealer')) {
            $dealerId = (string) $this->user('dealer')->dealer_id;
        }

        return app(PaymentValidationRules::class)->upsert($dealerId);
    }
}
