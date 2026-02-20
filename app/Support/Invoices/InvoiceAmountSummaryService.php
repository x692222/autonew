<?php

namespace App\Support\Invoices;

use App\Models\Invoice\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class InvoiceAmountSummaryService
{
    public function __construct(
        private readonly InvoiceTotalsCalculator $totalsCalculator
    ) {
    }

    public function applyComputedTotalAmount(Builder $query, string $alias = 'total_amount', ?string $table = null): Builder
    {
        $table ??= $query->getModel()->getTable();
        $subtractive = "'" . implode("','", InvoiceSectionOptions::subtractiveValues()) . "'";
        $signedTotal = "(CASE WHEN ili.section IN ({$subtractive}) THEN -ili.total ELSE ili.total END)";
        $vatRate = "COALESCE({$table}.vat_percentage, 0)";
        $vatPortion = "(CASE WHEN {$table}.vat_enabled = 1 AND {$vatRate} > 0 THEN ({$signedTotal} * {$vatRate} / (100 + {$vatRate})) ELSE 0 END)";
        $signedPayable = "(CASE WHEN ili.is_vat_exempt = 1 THEN ({$signedTotal} - {$vatPortion}) ELSE {$signedTotal} END)";

        return $query->selectRaw(
            "(SELECT ROUND(COALESCE(SUM({$signedPayable}), 0), 2) FROM invoice_line_items ili WHERE ili.invoice_id = {$table}.id) AS {$alias}"
        );
    }

    public function totalForInvoice(Invoice $invoice): float
    {
        if ($invoice->relationLoaded('lineItems')) {
            $lineItems = $invoice->lineItems
                ->map(fn ($lineItem) => [
                    'section' => $lineItem->section?->value ?? (string) $lineItem->section,
                    'total' => (float) $lineItem->total,
                    'is_vat_exempt' => (bool) $lineItem->is_vat_exempt,
                ])
                ->values()
                ->all();

            $totals = $this->totalsCalculator->calculate(
                lineItems: $lineItems,
                vatEnabled: (bool) $invoice->vat_enabled,
                vatPercentage: $invoice->vat_percentage !== null ? (float) $invoice->vat_percentage : null
            );

            return round((float) ($totals['total_amount'] ?? 0), 2);
        }

        return round((float) (
            Invoice::query()
                ->whereKey($invoice->getKey())
                ->tap(fn (Builder $query) => $this->applyComputedTotalAmount($query))
                ->value('total_amount') ?? 0
        ), 2);
    }

    public function sumComputedTotalAmountForInvoices(Builder $query): float
    {
        $subQuery = (clone $query)
            ->select('invoices.id')
            ->tap(fn (Builder $builder) => $this->applyComputedTotalAmount($builder))
            ->toBase();

        return round((float) (DB::query()->fromSub($subQuery, 'invoice_amounts')->sum('total_amount') ?? 0), 2);
    }
}
