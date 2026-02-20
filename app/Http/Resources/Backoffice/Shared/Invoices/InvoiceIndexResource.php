<?php

namespace App\Http\Resources\Backoffice\Shared\Invoices;

use App\Models\Invoice\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invoice */
class InvoiceIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $context = (array) $request->attributes->get('invoice_context', []);
        $totalAmount = (float) ($this->total_amount ?? 0);
        $totalPaidAmount = (float) ($this->paid_amount ?? 0);
        $totalDue = max(0, round($totalAmount - $totalPaidAmount, 2));

        return [
            'id' => $this->id,
            'dealer_id' => $this->dealer_id,
            'dealer_name' => $this->dealer?->name,
            'invoice_identifier' => (string) $this->invoice_identifier,
            'invoice_date' => optional($this->invoice_date)?->format('Y-m-d'),
            'is_fully_paid' => (bool) $this->is_fully_paid,
            'payable_by' => optional($this->payable_by)?->format('Y-m-d'),
            'customer_firstname' => $this->customer?->firstname ?? '-',
            'customer_lastname' => $this->customer?->lastname ?? '-',
            'customer_name' => trim(($this->customer?->firstname ?? '') . ' ' . ($this->customer?->lastname ?? '')) ?: '-',
            'total_amount' => $totalAmount,
            'total_paid_amount' => $totalPaidAmount,
            'total_due' => $totalDue,
            'total_items_general_accessories' => (int) ($this->total_items_general_accessories ?? 0),
            'notes_count' => (int) ($this->notes_count ?? 0),
            'can' => [
                'edit' => (bool) ($context['can_edit'] ?? false),
                'delete' => (bool) ($context['can_delete'] ?? false),
                'export' => (bool) ($context['can_export'] ?? false),
                'show_notes' => (bool) ($context['can_show_notes'] ?? false),
            ],
        ];
    }
}
