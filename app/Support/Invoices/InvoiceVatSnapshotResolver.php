<?php

namespace App\Support\Invoices;

use App\Models\Dealer\Dealer;
use App\Support\Settings\DealerSettingsResolver;
use App\Support\Settings\SystemSettingsResolver;

class InvoiceVatSnapshotResolver
{
    public function __construct(
        private readonly DealerSettingsResolver $dealerSettingsResolver,
        private readonly SystemSettingsResolver $systemSettingsResolver
    ) {
    }

    public function forSystem(): array
    {
        $settings = $this->systemSettingsResolver->resolve([
            'system_is_vat_registered',
            'system_vat_percentage',
            'system_vat_number',
        ]);

        return [
            'vat_enabled' => (bool) ($settings['system_is_vat_registered'] ?? false),
            'vat_percentage' => $settings['system_vat_percentage'] !== null ? (float) $settings['system_vat_percentage'] : null,
            'vat_number' => $settings['system_vat_number'] ? (string) $settings['system_vat_number'] : null,
        ];
    }

    public function forDealer(Dealer $dealer): array
    {
        $settings = $this->dealerSettingsResolver->resolve($dealer->id, [
            'dealer_is_vat_registered',
            'dealer_vat_percentage',
            'dealer_vat_number',
        ]);

        return [
            'vat_enabled' => (bool) ($settings['dealer_is_vat_registered'] ?? false),
            'vat_percentage' => $settings['dealer_vat_percentage'] !== null ? (float) $settings['dealer_vat_percentage'] : null,
            'vat_number' => $settings['dealer_vat_number'] ? (string) $settings['dealer_vat_number'] : null,
        ];
    }

    public function hasMatchingVatSnapshot(array $current, array $snapshot): bool
    {
        return (bool) ($current['vat_enabled'] ?? false) === (bool) ($snapshot['vat_enabled'] ?? false)
            && (float) ($current['vat_percentage'] ?? 0) === (float) ($snapshot['vat_percentage'] ?? 0);
    }
}

