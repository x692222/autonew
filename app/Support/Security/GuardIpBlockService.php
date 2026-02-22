<?php

namespace App\Support\Security;

use App\Models\Security\BlockedIp;

class GuardIpBlockService
{
    private const FAILURE_THRESHOLD = 20;

    public function recordFailedAttempt(?string $ip, ?string $guardName): ?BlockedIp
    {
        $normalizedIp = $this->normalizeIp($ip);
        $normalizedGuard = $this->normalizeGuard($guardName);

        if ($normalizedIp === null || $normalizedGuard === null) {
            return null;
        }

        /** @var BlockedIp $record */
        $record = BlockedIp::query()->firstOrCreate(
            [
                'ip_address' => $normalizedIp,
                'guard_name' => $normalizedGuard,
            ],
            [
                'failed_attempts' => 0,
                'blocked_at' => null,
                'country' => null,
            ]
        );

        if ($record->blocked_at !== null) {
            return $record;
        }

        $record->failed_attempts = max(0, (int) $record->failed_attempts) + 1;
        if ($record->failed_attempts >= self::FAILURE_THRESHOLD) {
            $record->blocked_at = now();
        }

        $record->save();

        return $record;
    }

    public function isIpBlocked(?string $ip): bool
    {
        $normalizedIp = $this->normalizeIp($ip);
        if ($normalizedIp === null) {
            return false;
        }

        return BlockedIp::query()
            ->where('ip_address', $normalizedIp)
            ->whereNotNull('blocked_at')
            ->exists();
    }

    public function unblock(BlockedIp $blockedIp): bool
    {
        return (bool) $blockedIp->delete();
    }

    public function guardOptions(): array
    {
        return [
            ['label' => 'Backoffice', 'value' => 'backoffice'],
            ['label' => 'Dealer', 'value' => 'dealer'],
        ];
    }

    private function normalizeIp(?string $ip): ?string
    {
        $value = trim((string) $ip);
        return $value === '' ? null : $value;
    }

    private function normalizeGuard(?string $guardName): ?string
    {
        $value = trim((string) $guardName);
        if ($value === '') {
            return null;
        }

        return in_array($value, ['backoffice', 'dealer'], true) ? $value : null;
    }
}
