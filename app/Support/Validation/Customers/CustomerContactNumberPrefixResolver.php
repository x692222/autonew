<?php

namespace App\Support\Validation\Customers;

use App\Models\Dealer\Dealer;
use App\Support\Resolvers\Settings\DealerSettingsResolver;
use App\Support\Resolvers\Settings\SystemSettingsResolver;
use Illuminate\Http\Request;

class CustomerContactNumberPrefixResolver
{
    public function __construct(
        private readonly SystemSettingsResolver $systemSettingsResolver,
        private readonly DealerSettingsResolver $dealerSettingsResolver
    ) {
    }

    public function resolveForRequest(Request $request): string
    {
        $dealerId = $this->resolveDealerId($request);

        if ($dealerId !== null) {
            return $this->normalizePrefix((string) $this->dealerSettingsResolver->get($dealerId, 'contact_no_prefix', ''));
        }

        return $this->normalizePrefix((string) $this->systemSettingsResolver->get('contact_no_prefix', ''));
    }

    private function resolveDealerId(Request $request): ?string
    {
        $dealerParam = $request->route('dealer');
        if ($dealerParam instanceof Dealer) {
            return (string) $dealerParam->id;
        }

        if (is_scalar($dealerParam) && (string) $dealerParam !== '') {
            return (string) $dealerParam;
        }

        $dealerActor = $request->user('dealer');
        if ($dealerActor && filled($dealerActor->dealer_id)) {
            return (string) $dealerActor->dealer_id;
        }

        return null;
    }

    private function normalizePrefix(string $prefix): string
    {
        return preg_replace('/\s+/', '', $prefix) ?? '';
    }
}

