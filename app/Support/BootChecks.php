<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Refuses to boot with configurations that would be unsafe in production (SEC-011, SEC-015, FR-053).
 */
class BootChecks
{
    public const AI_PROVIDERS = ['gemini', 'fake'];

    public const MAX_SESSION_MINUTES = 30;

    /**
     * @throws RuntimeException
     */
    public static function assertSafeConfiguration(
        string $environment,
        bool $demoMode,
        bool $debug,
        string $aiProvider = 'gemini',
        int $sessionLifetime = self::MAX_SESSION_MINUTES,
        string $sessionDriver = 'file',
    ): void {
        if (! in_array($aiProvider, self::AI_PROVIDERS, true)) {
            throw new RuntimeException("Unknown RAFIQ_AI_PROVIDER \"{$aiProvider}\". Use one of: ".implode(', ', self::AI_PROVIDERS).'.');
        }

        if ($environment !== 'production') {
            return;
        }

        if ($aiProvider !== 'gemini') {
            throw new RuntimeException('RAFIQ_AI_PROVIDER must be "gemini" in production: the fake provider is for local development and tests only.');
        }

        if ($demoMode) {
            throw new RuntimeException('Rafiq demo mode (RAFIQ_DEMO_MODE) must not be enabled in production: it runs clinical rules that are not medically approved.');
        }

        if ($debug) {
            throw new RuntimeException('APP_DEBUG must be false in production.');
        }

        // The privacy page promises a 30-minute session (PRIV-005).
        if ($sessionLifetime < 1 || $sessionLifetime > self::MAX_SESSION_MINUTES) {
            throw new RuntimeException('SESSION_LIFETIME must be between 1 and '.self::MAX_SESSION_MINUTES.' minutes in production.');
        }

        // Laravel's database session table stores IP address and user agent (Database-Design §2).
        if ($sessionDriver === 'database') {
            throw new RuntimeException('SESSION_DRIVER must not be "database" in production: it stores IP addresses and user agents. Use redis or file.');
        }
    }
}
