<?php

namespace Tests\Unit;

use App\Support\BootChecks;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BootChecksTest extends TestCase
{
    public function test_demo_mode_is_refused_in_production(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/demo mode/');

        BootChecks::assertSafeConfiguration(environment: 'production', demoMode: true, debug: false);
    }

    public function test_debug_mode_is_refused_in_production(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/APP_DEBUG/');

        BootChecks::assertSafeConfiguration(environment: 'production', demoMode: false, debug: true);
    }

    public function test_demo_and_debug_are_allowed_outside_production(): void
    {
        BootChecks::assertSafeConfiguration(environment: 'local', demoMode: true, debug: true);
        BootChecks::assertSafeConfiguration(environment: 'staging', demoMode: true, debug: false);

        $this->addToAssertionCount(1);
    }

    public function test_safe_production_configuration_boots(): void
    {
        BootChecks::assertSafeConfiguration(environment: 'production', demoMode: false, debug: false);

        $this->addToAssertionCount(1);
    }
}
