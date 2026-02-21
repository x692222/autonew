<?php

namespace App\Actions\Backoffice\Shared\Customers;

use App\Models\Dealer\Dealer;
use App\Models\Quotation\Customer;
use App\Support\Security\TenantScopeEnforcer;

class UpsertCustomerAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(?Customer $customer, array $data, ?Dealer $dealer = null): Customer
    {
        if ($customer) {
            $this->tenantScopeEnforcer->assertSameDealerScope(
                actualDealerId: $customer->dealer_id,
                expectedDealerId: $dealer?->id,
                field: 'customer_id'
            );
        }

        $payload = [
            'type' => $data['type'],
            'title' => $data['title'] ?? null,
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'] ?? null,
            'id_number' => $data['id_number'] ?? null,
            'email' => $data['email'] ?? null,
            'contact_number' => preg_replace('/\s+/', '', (string) ($data['contact_number'] ?? '')) ?: null,
            'address' => $data['address'],
            'vat_number' => preg_replace('/\s+/', '', (string) ($data['vat_number'] ?? '')) ?: null,
            'dealer_id' => $dealer?->id,
        ];

        if (! $customer) {
            return Customer::query()->create($payload);
        }

        $customer->update($payload);

        return $customer->refresh();
    }
}
