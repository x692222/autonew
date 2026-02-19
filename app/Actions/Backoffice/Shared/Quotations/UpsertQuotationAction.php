<?php

namespace App\Actions\Backoffice\Shared\Quotations;

use App\Models\Dealer\Dealer;
use App\Models\Quotation\Quotation;
use App\Support\Quotations\QuotationIdentifierGenerator;
use App\Support\Quotations\QuotationTotalsCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpsertQuotationAction
{
    public function __construct(
        private readonly QuotationIdentifierGenerator $identifierGenerator,
        private readonly QuotationTotalsCalculator $totalsCalculator
    ) {
    }

    public function execute(
        ?Quotation $quotation,
        array $data,
        Model $actor,
        ?Dealer $dealer,
        array $vatSnapshot
    ): Quotation {
        return DB::transaction(function () use ($quotation, $data, $actor, $dealer, $vatSnapshot): Quotation {
            $lineItems = collect((array) ($data['line_items'] ?? []))
                ->map(function (array $lineItem): array {
                    $amount = round((float) ($lineItem['amount'] ?? 0), 2);
                    $qty = round((float) ($lineItem['qty'] ?? 0), 2);
                    $total = round($amount * $qty, 2);

                    return [
                        'section' => (string) ($lineItem['section'] ?? ''),
                        'sku' => $lineItem['sku'] ? (string) $lineItem['sku'] : null,
                        'description' => (string) ($lineItem['description'] ?? ''),
                        'amount' => $amount,
                        'qty' => $qty,
                        'total' => $total,
                        'stock_id' => $lineItem['stock_id'] ?: null,
                        'is_vat_exempt' => (bool) ($lineItem['is_vat_exempt'] ?? false),
                    ];
                })
                ->values()
                ->all();

            $totals = $this->totalsCalculator->calculate(
                lineItems: $lineItems,
                vatEnabled: (bool) ($vatSnapshot['vat_enabled'] ?? false),
                vatPercentage: $vatSnapshot['vat_percentage'] !== null ? (float) $vatSnapshot['vat_percentage'] : null
            );

            $isCreate = $quotation === null;
            $hasCustomIdentifier = $isCreate
                ? (bool) ($data['has_custom_quote_identifier'] ?? false)
                : (bool) ($quotation?->has_custom_quote_identifier ?? false);
            $quoteIdentifier = $isCreate
                ? ($hasCustomIdentifier
                    ? (string) $data['quote_identifier']
                    : $this->identifierGenerator->next($dealer?->id))
                : (string) $quotation->quote_identifier;

            $duplicateExists = Quotation::query()
                ->where('quote_identifier', $quoteIdentifier)
                ->when(
                    $dealer,
                    fn ($query) => $query->where('dealer_id', $dealer->id),
                    fn ($query) => $query->whereNull('dealer_id')
                )
                ->whereNull('deleted_at')
                ->when($quotation, fn ($query) => $query->where('id', '!=', $quotation->id))
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'quote_identifier' => ['Quotation reference must be unique in this scope.'],
                ]);
            }

            $quotationDate = Carbon::parse((string) $data['quotation_date'])->startOfDay();
            $validForDays = (int) $data['valid_for_days'];
            $validUntil = $quotationDate->copy()->addDays($validForDays);

            $payload = [
                'dealer_id' => $dealer?->id,
                'customer_id' => $data['customer_id'] ?: null,
                'quote_identifier' => $quoteIdentifier,
                'has_custom_quote_identifier' => $hasCustomIdentifier,
                'quotation_date' => $quotationDate->toDateString(),
                'valid_for_days' => $validForDays,
                'valid_until' => $validUntil->toDateString(),
                'vat_enabled' => (bool) ($vatSnapshot['vat_enabled'] ?? false),
                'vat_percentage' => $vatSnapshot['vat_percentage'],
                'vat_number' => $vatSnapshot['vat_number'],
                'subtotal_before_vat' => $totals['subtotal_before_vat'],
                'vat_amount' => $totals['vat_amount'],
                'total_amount' => $totals['total_amount'],
                'updated_by_type' => get_class($actor),
                'updated_by_id' => $actor->getKey(),
            ];

            if (! $quotation) {
                $payload['created_by_type'] = get_class($actor);
                $payload['created_by_id'] = $actor->getKey();
            }

            $quotation = $quotation
                ? tap($quotation)->update($payload)
                : Quotation::query()->create($payload);

            $quotation->lineItems()->delete();

            if ($lineItems !== []) {
                $quotation->lineItems()->createMany(
                    collect($lineItems)
                        ->map(fn (array $lineItem) => [
                            ...$lineItem,
                            'dealer_id' => $dealer?->id,
                        ])
                        ->all()
                );
            }

            return $quotation->fresh(['customer', 'lineItems']);
        });
    }
}
