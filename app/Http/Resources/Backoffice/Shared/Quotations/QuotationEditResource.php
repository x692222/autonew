<?php

namespace App\Http\Resources\Backoffice\Shared\Quotations;

use App\Models\Quotation\Quotation;
use App\Support\Stock\AssociatedStockPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Quotation */
class QuotationEditResource extends JsonResource
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
            'quote_identifier' => (string) $this->quote_identifier,
            'has_custom_quote_identifier' => (bool) $this->has_custom_quote_identifier,
            'quotation_date' => optional($this->quotation_date)?->format('Y-m-d'),
            'valid_for_days' => (int) $this->valid_for_days,
            'valid_until' => optional($this->valid_until)?->format('Y-m-d'),
            'vat_enabled' => (bool) $this->vat_enabled,
            'vat_percentage' => $this->vat_percentage !== null ? (float) $this->vat_percentage : null,
            'vat_number' => $this->vat_number,
            'subtotal_before_vat' => (float) $this->subtotal_before_vat,
            'vat_amount' => (float) $this->vat_amount,
            'total_amount' => (float) $this->total_amount,
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
