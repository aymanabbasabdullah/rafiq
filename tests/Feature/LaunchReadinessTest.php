<?php

namespace Tests\Feature;

use App\Support\BootChecks;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * Module-12 automated launch checks: schema holds no health or identifying columns (Module-12 §10),
 * route surface is closed (§11) and the retention/monitoring tasks are scheduled (M12-FR-01/02/05).
 */
class LaunchReadinessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Column names that would mean visitor health data or identifiers are being stored.
     *
     * @var list<string>
     */
    private const FORBIDDEN_COLUMNS = ['answers', 'answer', 'symptoms', 'message', 'messages', 'response_text', 'ip_address', 'user_agent'];

    public function test_no_table_has_a_column_for_answers_symptoms_messages_or_visitor_identifiers(): void
    {
        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            // Laravel's default `sessions` table is unused unless the database session driver is chosen (Database-Design §2).
            if ($table === 'sessions') {
                continue;
            }

            $found = array_intersect(Schema::getColumnListing($table), self::FORBIDDEN_COLUMNS);

            $this->assertSame([], array_values($found), "Table [{$table}] has forbidden columns.");
        }
    }

    public function test_the_example_environment_does_not_use_the_database_session_driver(): void
    {
        $example = (string) file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression('/^SESSION_DRIVER=(file|redis)$/m', $example);
        $this->assertMatchesRegularExpression('/^LOG_STACK=daily$/m', $example);
    }

    public function test_there_is_no_public_registration_and_every_admin_route_requires_authentication(): void
    {
        $guestAdminRoutes = ['admin/login', 'admin/forgot-password', 'admin/reset-password', 'admin/reset-password/{token}'];

        /** @var RoutingRoute $route */
        foreach (Route::getRoutes() as $route) {
            $this->assertStringNotContainsString('register', $route->uri());

            if (str_starts_with($route->uri(), 'admin') && ! in_array($route->uri(), $guestAdminRoutes, true)) {
                $this->assertContains('auth', $route->gatherMiddleware(), "Admin route [{$route->uri()}] is not behind auth.");
            }
        }
    }

    public function test_every_write_route_is_rate_limited(): void
    {
        /** @var RoutingRoute $route */
        foreach (Route::getRoutes() as $route) {
            // Framework routes: Laravel's signed local-disk upload URL (storage/{path}) is not part of the app surface.
            if (array_diff($route->methods(), ['GET', 'HEAD']) === [] || str_starts_with($route->uri(), '_') || $route->getName() === 'storage.local.upload') {
                continue;
            }

            $throttled = array_filter($route->gatherMiddleware(), fn (mixed $middleware): bool => is_string($middleware) && str_starts_with($middleware, 'throttle:'));

            $this->assertNotSame([], $throttled, "Write route [{$route->uri()}] has no throttle.");
        }
    }

    public function test_retention_review_and_budget_tasks_are_scheduled(): void
    {
        $commands = collect(app(Schedule::class)->events())->map(fn ($event): string => (string) $event->command)->implode("\n");

        foreach (['rafiq:prune', 'rafiq:content:review-reminders', 'rafiq:ai:budget-check', 'queue:prune-failed'] as $command) {
            $this->assertStringContainsString($command, $commands);
        }
    }

    public function test_the_health_endpoint_is_up(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_production_refuses_long_sessions_and_the_database_session_driver(): void
    {
        BootChecks::assertSafeConfiguration('production', false, false, 'gemini', 30, 'redis');
        BootChecks::assertSafeConfiguration('local', false, false, 'gemini', 120, 'database');

        foreach ([[120, 'file', 'SESSION_LIFETIME'], [30, 'database', 'SESSION_DRIVER']] as [$lifetime, $driver, $setting]) {
            try {
                BootChecks::assertSafeConfiguration('production', false, false, 'gemini', $lifetime, $driver);
                $this->fail("Production booted with an unsafe {$setting}.");
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString($setting, $exception->getMessage());
            }
        }

        $this->assertSame(30, (int) config('session.lifetime'));
    }
}
