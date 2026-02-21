<?php

namespace App\Actions\Backoffice\Shared\Stocks;

use App\Models\Dealer\Dealer;
use App\Models\Stock\Stock;
use App\Support\Security\TenantScopeEnforcer;

class SetStockActiveStatusAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Stock $stock, Dealer $dealer, bool $isActive): void
    {
        $this->tenantScopeEnforcer->assertStockInDealerScope($stock, $dealer);
        $stock->update(['is_active' => $isActive]);
    }
}
