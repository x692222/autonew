<?php

namespace App\Http\Resources\Backoffice\Shared\Customers;

use App\Models\Quotation\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class CustomerIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $context = (array) $request->attributes->get('customer_context', []);

        return [
            'id' => $this->id,
            'type' => strtoupper((string) ($this->type?->value ?? $this->type)),
            'title' => $this->title,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'full_name' => trim(($this->firstname ?? '') . ' ' . ($this->lastname ?? '')) ?: '-',
            'id_number' => $this->id_number,
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'quotations_count' => (int) ($this->quotations_count ?? 0),
            'invoices_count' => (int) ($this->invoices_count ?? 0),
            'payments_count' => (int) ($this->payments_count ?? 0),
            'created_at' => optional($this->created_at)?->format('Y-m-d'),
            'can' => [
                'view' => (bool) ($context['can_view'] ?? false),
                'edit' => (bool) ($context['can_edit'] ?? false),
                'delete' => (bool) ($context['can_delete'] ?? false),
            ],
        ];
    }
}
