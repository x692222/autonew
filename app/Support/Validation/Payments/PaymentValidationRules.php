<?php

namespace App\Support\Validation\Payments;

use App\Enums\PaymentMethodEnum;
use Illuminate\Validation\Rule;

class PaymentValidationRules
{
    public function index(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', Rule::in(PaymentMethodEnum::values())],
            'banking_detail_id' => ['nullable', 'uuid'],
            'verification_status' => ['nullable', Rule::in(['all', 'pending', 'verified', 'unverified'])],
            'payment_date_from' => ['nullable', 'date_format:Y-m-d', 'required_with:payment_date_to', 'before:payment_date_to'],
            'payment_date_to' => ['nullable', 'date_format:Y-m-d', 'required_with:payment_date_from', 'after:payment_date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:200'],
            'sortBy' => ['nullable', 'in:payment_date,invoice_identifier,payment_method,amount,is_approved,last_verified_at,created_at'],
            'descending' => ['nullable'],
        ];
    }

    public function upsert(?string $dealerId = null): array
    {
        return [
            'description' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'payment_method' => ['required', Rule::in(PaymentMethodEnum::values())],
            'banking_detail_id' => [
                'nullable',
                'required_if:payment_method,eft',
                'uuid',
                Rule::exists('banking_details', 'id')->where(function ($query) use ($dealerId) {
                    return $dealerId
                        ? $query->where('dealer_id', $dealerId)
                        : $query->whereNull('dealer_id');
                }),
            ],
        ];
    }
}
