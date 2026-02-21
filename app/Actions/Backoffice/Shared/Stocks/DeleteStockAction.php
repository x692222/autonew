<?php

namespace App\Actions\Backoffice\Shared\Stocks;

use App\Models\Dealer\Dealer;
use App\Models\Stock\Stock;
use App\Support\Security\TenantScopeEnforcer;

class DeleteStockAction
{
    public function __construct(private readonly TenantScopeEnforcer $tenantScopeEnforcer)
    {
    }

    public function execute(Stock $stock, Dealer $dealer): void
    {
        $this->tenantScopeEnforcer->assertStockInDealerScope($stock, $dealer);
        $stock->delete();
    }
}
