<?php

namespace App\Support\Settings;

class DocumentSettingsPresenter
{
    public function __construct(
        private readonly DealerSettingsResolver $dealerSettingsResolver,
        private readonly SystemSettingsResolver $systemSettingsResolver
    ) {
    }

    public function system(bool $includeContactNoPrefix = false): array
    {
        $keys = ['system_currency'];
        if ($includeContactNoPrefix) {
            $keys[] = 'contact_no_prefix';
        }

        $settings = $this->systemSettingsResolver->resolve($keys);

        return [
            'currencySymbol' => (string) ($settings['system_currency'] ?? 'N$'),
            'contactNoPrefix' => $includeContactNoPrefix ? (string) ($settings['contact_no_prefix'] ?? '') : null,
        ];
    }

    public function dealer(string $dealerId, bool $includeContactNoPrefix = false): array
    {
        $keys = ['dealer_currency'];
        if ($includeContactNoPrefix) {
            $keys[] = 'contact_no_prefix';
        }

        $settings = $this->dealerSettingsResolver->resolve($dealerId, $keys);

        return [
            'currencySymbol' => (string) ($settings['dealer_currency'] ?? 'N$'),
            'contactNoPrefix' => $includeContactNoPrefix ? (string) ($settings['contact_no_prefix'] ?? '') : null,
        ];
    }
}

