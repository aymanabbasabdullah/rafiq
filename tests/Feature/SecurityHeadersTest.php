<?php

namespace Tests\Feature;

use App\Http\Middleware\NoStoreCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_send_the_required_security_headers(): void
    {
        $response = $this->get('/ar');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $this->assertStringContainsString('camera=()', (string) $response->headers->get('Permissions-Policy'));
    }

    public function test_content_security_policy_allows_only_this_origin(): void
    {
        $policy = (string) $this->get('/ar')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9]+'/", $policy);
        $this->assertStringNotContainsString('https://', $policy);
    }

    public function test_script_tags_carry_the_policy_nonce(): void
    {
        $response = $this->get('/ar');

        preg_match("/'nonce-([A-Za-z0-9]+)'/", (string) $response->headers->get('Content-Security-Policy'), $matches);

        $this->assertNotEmpty($matches[1] ?? null);
        $response->assertSee('nonce="'.$matches[1].'"', false);
    }

    public function test_policy_is_relaxed_only_for_the_local_debug_error_page(): void
    {
        Route::middleware('web')->get('/ar/__test/boom', fn () => throw new \RuntimeException('boom'));

        config(['app.debug' => true]);
        $this->get('/ar/__test/boom')->assertStatus(500)->assertHeaderMissing('Content-Security-Policy');

        config(['app.debug' => false]);
        $this->get('/ar/__test/boom')->assertStatus(500)->assertHeader('Content-Security-Policy');
    }

    public function test_hsts_is_sent_only_over_https(): void
    {
        $this->get('/ar')->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/ar')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_no_store_middleware_prevents_caching_of_sensitive_responses(): void
    {
        Route::middleware(['web', NoStoreCache::class])->get('/__test/sensitive', fn () => 'ok');

        $response = $this->get('/__test/sensitive');

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
    }
}
