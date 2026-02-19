<?php

namespace App\Http\Resources\Backoffice\Shared\Invoices;

use App\Models\Stock\Stock;
use App\Models\Invoice\Invoice;
use App\Support\StockHelper;
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
                ->map(fn ($lineItem) => $this->mapAssociatedStock($lineItem->stock))
                ->values()
                ->all(),
        ];
    }

    private function mapAssociatedStock(?Stock $stock): array
    {
        $meta = StockHelper::stockRelationMeta()[$stock?->type] ?? null;
        $relation = $meta['relation'] ?? null;
        $typed = $relation ? $stock?->{$relation} : null;
        $cap = $meta['properties'] ?? [];
        $typedAttributes = $typed?->getAttributes() ?? [];

        $hasTypedAttribute = static fn (string $attribute) => array_key_exists($attribute, $typedAttributes);

        $fields = [
            'make' => (bool) ($cap['make'] ?? false),
            'model' => (bool) ($cap['model'] ?? false),
            'millage' => (bool) ($cap['millage'] ?? false),
            'is_police_clearance_ready' => (bool) ($cap['police_clearance'] ?? false),
            'condition' => $hasTypedAttribute('condition'),
            'is_import' => (bool) ($cap['import'] ?? false),
            'gearbox_type' => (bool) ($cap['gearbox'] ?? false),
            'drive_type' => (bool) ($cap['drive'] ?? false),
            'fuel_type' => (bool) ($cap['fuel'] ?? false),
            'color' => (bool) ($cap['color'] ?? false),
        ];

        return [
            'stock_id' => $stock?->id,
            'internal_reference' => $stock?->internal_reference,
            'name' => $stock?->name,
            'is_active' => (bool) ($stock?->is_active ?? false),
            'is_sold' => (bool) ($stock?->is_sold ?? false),
            'type' => (string) ($stock?->type ?? '-'),
            'fields' => $fields,
            'make' => $fields['make'] ? $typed?->make?->name : null,
            'model' => $fields['model'] ? $typed?->model?->name : null,
            'millage' => $fields['millage'] ? $typed?->millage : null,
            'is_police_clearance_ready' => $fields['is_police_clearance_ready']
                ? ($typed?->is_police_clearance_ready?->value ?? $typed?->is_police_clearance_ready)
                : null,
            'condition' => $fields['condition'] ? $typed?->condition : null,
            'is_import' => $fields['is_import'] ? $typed?->is_import : null,
            'gearbox_type' => $fields['gearbox_type'] ? $typed?->gearbox_type : null,
            'drive_type' => $fields['drive_type'] ? $typed?->drive_type : null,
            'fuel_type' => $fields['fuel_type'] ? $typed?->fuel_type : null,
            'color' => $fields['color'] ? $typed?->color : null,
        ];
    }
}
