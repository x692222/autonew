<?php

namespace App\Actions\Backoffice\Shared\Customers;

use App\Models\Dealer\Dealer;
use App\Models\Quotation\Customer;
use App\Support\Security\TenantScopeEnforcer;

class DeleteCustomerAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Customer $customer, ?Dealer $dealer = null): void
    {
        $this->tenantScopeEnforcer->assertSameDealerScope($customer->dealer_id, $dealer?->id, 'customer_id');
        $customer->delete();
    }
}
