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

    /**
     * @throws RuntimeException
     */
    public static function assertSafeConfiguration(string $environment, bool $demoMode, bool $debug, string $aiProvider = 'gemini'): void
    {
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
    }
}
