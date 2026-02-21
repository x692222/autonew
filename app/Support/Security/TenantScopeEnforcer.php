<?php

namespace App\Support\Security;

use App\Models\Billing\BankingDetail;
use App\Models\Dealer\Dealer;
use App\Models\Dealer\DealerBranch;
use App\Models\Invoice\Invoice;
use App\Models\Payments\Payment;
use App\Models\Quotation\Customer;
use App\Models\Quotation\Quotation;
use App\Models\Stock\Stock;
use Illuminate\Validation\ValidationException;

class TenantScopeEnforcer
{
    public function assertSameDealerScope(?string $actualDealerId, ?string $expectedDealerId, string $field): void
    {
        if ((string) ($actualDealerId ?? '') !== (string) ($expectedDealerId ?? '')) {
            throw ValidationException::withMessages([
                $field => ['Resource is outside the current tenant scope.'],
            ]);
        }
    }

    public function assertCustomerInScope(?string $customerId, ?Dealer $dealer, string $field = 'customer_id'): void
    {
        if (! $customerId) {
            return;
        }

        $exists = Customer::query()
            ->whereKey($customerId)
            ->when(
                $dealer,
                fn ($query) => $query->where('dealer_id', $dealer->id),
                fn ($query) => $query->whereNull('dealer_id')
            )
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                $field => ['Selected customer is outside the current tenant scope.'],
            ]);
        }
    }

    public function assertStockIdsInScope(array $stockIds, ?Dealer $dealer, string $field = 'line_items'): void
    {
        $ids = collect($stockIds)->filter()->map(fn ($id) => (string) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        $count = Stock::query()
            ->whereIn('id', $ids->all())
            ->whereHas('branch', function ($query) use ($dealer) {
                return $dealer
                    ? $query->where('dealer_id', $dealer->id)
                    : $query->whereNull('dealer_id');
            })
            ->count();

        if ($count !== $ids->count()) {
            throw ValidationException::withMessages([
                $field => ['One or more stock items are outside the current tenant scope.'],
            ]);
        }
    }

    public function assertBranchInDealerScope(string $branchId, Dealer $dealer, string $field = 'branch_id'): void
    {
        $exists = DealerBranch::query()
            ->whereKey($branchId)
            ->where('dealer_id', $dealer->id)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                $field => ['Selected branch is outside the current tenant scope.'],
            ]);
        }
    }

    public function assertStockInDealerScope(Stock $stock, Dealer $dealer, string $field = 'stock'): void
    {
        $stock->loadMissing('branch:id,dealer_id');

        if ((string) ($stock->branch?->dealer_id ?? '') !== (string) $dealer->id) {
            throw ValidationException::withMessages([
                $field => ['Stock is outside the current tenant scope.'],
            ]);
        }
    }

    public function assertInvoiceInScope(Invoice $invoice, ?Dealer $dealer, string $field = 'invoice'): void
    {
        $this->assertSameDealerScope($invoice->dealer_id, $dealer?->id, $field);
    }

    public function assertQuotationInScope(Quotation $quotation, ?Dealer $dealer, string $field = 'quotation'): void
    {
        $this->assertSameDealerScope($quotation->dealer_id, $dealer?->id, $field);
    }

    public function assertPaymentMatchesInvoiceScope(Payment $payment, Invoice $invoice): void
    {
        if ((string) ($payment->invoice_id ?? '') !== (string) $invoice->id) {
            throw ValidationException::withMessages([
                'payment' => ['Payment does not belong to the specified invoice scope.'],
            ]);
        }

        $this->assertSameDealerScope($payment->dealer_id, $invoice->dealer_id, 'payment');
    }

    public function assertBankingDetailInScope(?string $bankingDetailId, ?string $dealerId): void
    {
        if (! $bankingDetailId) {
            return;
        }

        $exists = BankingDetail::query()
            ->whereKey($bankingDetailId)
            ->when(
                $dealerId,
                fn ($query) => $query->where('dealer_id', $dealerId),
                fn ($query) => $query->whereNull('dealer_id')
            )
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'banking_detail_id' => ['Selected banking detail is outside the current tenant scope.'],
            ]);
        }
    }
}
