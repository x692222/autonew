<?php

namespace App\Actions\Backoffice\Shared\Invoices;

use App\Models\Dealer\Dealer;
use App\Models\Invoice\Invoice;
use App\Models\Quotation\Quotation;
use App\Support\Invoices\InvoiceIdentifierGenerator;
use App\Support\Invoices\InvoiceTotalsCalculator;
use App\Support\LineItems\StoredLineItemUpsertService;
use App\Support\Security\TenantScopeEnforcer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpsertInvoiceAction
{
    public function __construct(
        private readonly InvoiceIdentifierGenerator $identifierGenerator,
        private readonly InvoiceTotalsCalculator $totalsCalculator,
        private readonly TenantScopeEnforcer $tenantScopeEnforcer,
        private readonly StoredLineItemUpsertService $storedLineItemUpsertService,
    ) {
    }

    public function execute(
        ?Invoice $invoice,
        array $data,
        Model $actor,
        ?Dealer $dealer,
        array $vatSnapshot,
        ?Quotation $quotation = null
    ): Invoice {
        try {
            return DB::transaction(function () use ($invoice, $data, $actor, $dealer, $vatSnapshot, $quotation): Invoice {
            if ($invoice) {
                $this->tenantScopeEnforcer->assertInvoiceInScope(invoice: $invoice, dealer: $dealer);
            }

            if ($quotation) {
                $this->tenantScopeEnforcer->assertQuotationInScope(quotation: $quotation, dealer: $dealer);
            }

            $this->tenantScopeEnforcer->assertCustomerInScope(
                customerId: $data['customer_id'] ?? null,
                dealer: $dealer
            );

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

            $this->tenantScopeEnforcer->assertStockIdsInScope(
                stockIds: collect($lineItems)->pluck('stock_id')->all(),
                dealer: $dealer
            );

            $lineItems = collect($lineItems)
                ->map(function (array $lineItem) use ($dealer): array {
                    $storedLineItem = $this->storedLineItemUpsertService->upsert(
                        dealer: $dealer,
                        lineItem: $lineItem
                    );

                    return [
                        ...$lineItem,
                        'stored_line_item_id' => $storedLineItem?->id,
                    ];
                })
                ->all();

            $totals = $this->totalsCalculator->calculate(
                lineItems: $lineItems,
                vatEnabled: (bool) ($vatSnapshot['vat_enabled'] ?? false),
                vatPercentage: $vatSnapshot['vat_percentage'] !== null ? (float) $vatSnapshot['vat_percentage'] : null
            );

            $isCreate = $invoice === null;
            $hasCustomIdentifier = $isCreate
                ? (bool) ($data['has_custom_invoice_identifier'] ?? false)
                : (bool) ($invoice?->has_custom_invoice_identifier ?? false);
            $invoiceIdentifier = $isCreate
                ? ($hasCustomIdentifier
                    ? (string) $data['invoice_identifier']
                    : $this->identifierGenerator->next($dealer?->id))
                : (string) $invoice->invoice_identifier;

            $duplicateExists = Invoice::query()
                ->withTrashed()
                ->where('invoice_identifier', $invoiceIdentifier)
                ->when(
                    $dealer,
                    fn ($query) => $query->where('dealer_id', $dealer->id),
                    fn ($query) => $query->whereNull('dealer_id')
                )
                ->when($invoice, fn ($query) => $query->where('id', '!=', $invoice->id))
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'invoice_identifier' => ['Invoice reference must be unique in this scope.'],
                ]);
            }

            if ($invoice) {
                $incomingCustomerId = $data['customer_id'] ?: null;
                $currentCustomerId = $invoice->customer_id ?: null;

                if ($invoice->payments()->exists() && (string) $incomingCustomerId !== (string) $currentCustomerId) {
                    throw ValidationException::withMessages([
                        'customer_id' => ['Customer cannot be changed after payments have been recorded.'],
                    ]);
                }
            }

            $invoiceDate = Carbon::parse((string) $data['invoice_date'])->startOfDay();
            $payableBy = !empty($data['payable_by'])
                ? Carbon::parse((string) $data['payable_by'])->startOfDay()->toDateString()
                : null;

            $payload = [
                'dealer_id' => $dealer?->id,
                'quotation_id' => $quotation?->id ?? $invoice?->quotation_id,
                'customer_id' => $data['customer_id'] ?: null,
                'invoice_identifier' => $invoiceIdentifier,
                'has_custom_invoice_identifier' => $hasCustomIdentifier,
                'invoice_date' => $invoiceDate->toDateString(),
                'payable_by' => $payableBy,
                'purchase_order_number' => $data['purchase_order_number'] ?: null,
                'payment_terms' => $data['payment_terms'] ?: null,
                'vat_enabled' => (bool) ($vatSnapshot['vat_enabled'] ?? false),
                'vat_percentage' => $vatSnapshot['vat_percentage'],
                'vat_number' => $vatSnapshot['vat_number'],
                'subtotal_before_vat' => $totals['subtotal_before_vat'],
                'vat_amount' => $totals['vat_amount'],
                'updated_by_type' => get_class($actor),
                'updated_by_id' => $actor->getKey(),
            ];

            if (! $invoice) {
                $payload['created_by_type'] = get_class($actor);
                $payload['created_by_id'] = $actor->getKey();
            }

            $invoice = $invoice
                ? tap($invoice)->update($payload)
                : Invoice::query()->create($payload);

            $invoice->lineItems()->delete();

            if ($lineItems !== []) {
                $invoice->lineItems()->createMany(
                    collect($lineItems)
                        ->map(fn (array $lineItem) => [
                            ...$lineItem,
                            'dealer_id' => $dealer?->id,
                        ])
                        ->all()
                );
            }

            return $invoice->fresh(['customer', 'lineItems']);
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateScopeIdentifierError($exception)) {
                throw ValidationException::withMessages([
                    'invoice_identifier' => ['Invoice reference must be unique in this scope.'],
                ]);
            }

            throw $exception;
        }
    }

    private function isDuplicateScopeIdentifierError(QueryException $exception): bool
    {
        $message = strtolower((string) $exception->getMessage());

        return str_contains($message, 'duplicate entry')
            && str_contains($message, 'invoices_scope_identifier_unique');
    }
}
