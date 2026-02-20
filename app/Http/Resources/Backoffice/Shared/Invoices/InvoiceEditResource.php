<?php

namespace App\Http\Resources\Backoffice\Shared\Invoices;

use App\Models\Invoice\Invoice;
use App\Support\Stock\AssociatedStockPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invoice */
class InvoiceEditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dealer_id' => $this->dealer_id,
            'customer_id' => $this->customer_id,
            'customer_label' => $this->customer
                ? trim(($this->customer->firstname ?? '') . ' ' . ($this->customer->lastname ?? ''))
                : null,
            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'type' => $this->customer->type?->value ?? (string) $this->customer->type,
                'title' => $this->customer->title,
                'firstname' => $this->customer->firstname,
                'lastname' => $this->customer->lastname,
                'id_number' => $this->customer->id_number,
                'email' => $this->customer->email,
                'contact_number' => $this->customer->contact_number,
                'address' => $this->customer->address,
                'vat_number' => $this->customer->vat_number,
            ] : null,
            'invoice_identifier' => (string) $this->invoice_identifier,
            'has_custom_invoice_identifier' => (bool) $this->has_custom_invoice_identifier,
            'invoice_date' => optional($this->invoice_date)?->format('Y-m-d'),
            'payable_by' => optional($this->payable_by)?->format('Y-m-d'),
            'purchase_order_number' => $this->purchase_order_number,
            'payment_method' => $this->payment_method,
            'payment_terms' => $this->payment_terms,
            'total_paid_amount' => (float) (
                $this->relationLoaded('payments')
                    ? $this->payments->sum('amount')
                    : ($this->payments()->sum('amount') ?? 0)
            ),
            'is_fully_paid' => (bool) $this->is_fully_paid,
            'vat_enabled' => (bool) $this->vat_enabled,
            'vat_percentage' => $this->vat_percentage !== null ? (float) $this->vat_percentage : null,
            'vat_number' => $this->vat_number,
            'subtotal_before_vat' => (float) $this->subtotal_before_vat,
            'vat_amount' => (float) $this->vat_amount,
            'total_amount' => app(\App\Support\Invoices\InvoiceAmountSummaryService::class)->totalForInvoice($this->resource),
            'line_items' => $this->lineItems
                ->map(fn ($lineItem) => [
                    'id' => $lineItem->id,
                    'section' => $lineItem->section?->value ?? (string) $lineItem->section,
                    'stock_id' => $lineItem->stock_id,
                    'sku' => $lineItem->sku,
                    'description' => $lineItem->description,
                    'amount' => (float) $lineItem->amount,
                    'qty' => (float) $lineItem->qty,
                    'total' => (float) $lineItem->total,
                    'is_vat_exempt' => (bool) $lineItem->is_vat_exempt,
                ])
                ->values()
                ->all(),
            'associated_stock' => $this->lineItems
                ->filter(fn ($lineItem) => $lineItem->stock)
                ->map(fn ($lineItem) => app(AssociatedStockPresenter::class)->present($lineItem->stock))
                ->values()
                ->all(),
        ];
    }
}
