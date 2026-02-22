<?php

namespace App\Support\Payments;

use App\Support\Resolvers\Settings\SystemSettingsResolver;

class PaymentVerificationAutoRefreshIntervalResolver
{
    public const MIN_SECONDS = 30;
    public const MAX_SECONDS = 7200;
    public const KEY = 'verify_payments_auto_refresh_seconds';

    public function __construct(
        private readonly SystemSettingsResolver $systemSettingsResolver,
    ) {
    }

    public function resolve(): int
    {
        $value = $this->systemSettingsResolver->get(self::KEY, self::MIN_SECONDS);
        $seconds = (int) $value;

        if ($seconds < self::MIN_SECONDS) {
            return self::MIN_SECONDS;
        }

        if ($seconds > self::MAX_SECONDS) {
            return self::MAX_SECONDS;
        }

        return $seconds;
    }
}

