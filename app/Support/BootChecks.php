<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Refuses to boot with configurations that would be unsafe in production (SEC-011, SEC-015, FR-053).
 */
class BootChecks
{
    /**
     * @throws RuntimeException
     */
    public static function assertSafeConfiguration(string $environment, bool $demoMode, bool $debug): void
    {
        if ($environment !== 'production') {
            return;
        }

        if ($demoMode) {
            throw new RuntimeException('Rafiq demo mode (RAFIQ_DEMO_MODE) must not be enabled in production: it runs clinical rules that are not medically approved.');
        }

        if ($debug) {
            throw new RuntimeException('APP_DEBUG must be false in production.');
        }
    }
}
