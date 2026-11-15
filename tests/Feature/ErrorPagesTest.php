<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_page_renders_the_bilingual_not_found_page_in_the_url_language(): void
    {
        $response = $this->get('/en/this-page-does-not-exist');

        $response->assertNotFound();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Errors/Error')
            ->where('status', 404)
            ->where('locale.current', 'en'));
    }

    public function test_not_found_page_falls_back_to_the_browser_language(): void
    {
        $response = $this->get('/nowhere', ['Accept-Language' => 'ar']);

        $response->assertNotFound();
        $response->assertSee('<html lang="ar" dir="rtl">', false);
        $response->assertInertia(fn (AssertableInertia $page) => $page->where('locale.current', 'ar'));
    }

    public function test_error_pages_still_send_security_headers(): void
    {
        $this->get('/nowhere')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    /**
     * @return array<string, array{int}>
     */
    public static function handledStatuses(): array
    {
        return [
            'forbidden' => [403],
            'session expired' => [419],
            'too many requests' => [429],
            'unavailable' => [503],
        ];
    }

    #[DataProvider('handledStatuses')]
    public function test_handled_statuses_render_the_error_page(int $status): void
    {
        Route::middleware('web')->get('/ar/__test/status', fn () => abort($status));

        $this->get('/ar/__test/status')
            ->assertStatus($status)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Errors/Error')
                ->where('status', $status));
    }

    public function test_server_errors_render_the_error_page_when_debug_is_off(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/ar/__test/boom', fn () => throw new \RuntimeException('boom'));

        $this->get('/ar/__test/boom')
            ->assertStatus(500)
            ->assertInertia(fn (AssertableInertia $page) => $page->where('status', 500));
    }

    public function test_json_requests_keep_json_error_responses(): void
    {
        $this->getJson('/en/this-page-does-not-exist')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json');
    }
}
