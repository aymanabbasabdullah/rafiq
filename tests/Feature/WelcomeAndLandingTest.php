<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WelcomeAndLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_visit_of_a_session_goes_to_the_welcome_screen(): void
    {
        $this->get('/', ['Accept-Language' => 'ar'])
            ->assertRedirect(route('public.welcome', ['locale' => 'ar']));
    }

    public function test_welcome_screen_is_shown_once_per_session(): void
    {
        $this->get('/ar/welcome')->assertOk()->assertSessionHas('welcomed', true);

        $this->get('/')->assertRedirect(route('public.landing', ['locale' => 'ar']));
    }

    public function test_welcome_screen_links_to_the_landing_page_and_auto_advances(): void
    {
        $this->get('/en/welcome')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Welcome')
            ->where('landingUrl', route('public.landing', ['locale' => 'en']))
            ->where('autoAdvanceSeconds', 4));
    }

    public function test_visiting_the_landing_page_directly_skips_the_welcome_screen_afterwards(): void
    {
        $this->get('/ar')->assertOk();

        $this->get('/')->assertRedirect(route('public.landing', ['locale' => 'ar']));
    }

    public function test_landing_page_lists_the_four_journeys_with_their_entry_links(): void
    {
        $this->get('/ar')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Landing')
            ->has('journeys', 4)
            ->where('journeys.0', ['key' => 'learn', 'url' => route('journeys.learn', ['locale' => 'ar'])])
            ->where('journeys.1', ['key' => 'concern', 'url' => route('journeys.concern', ['locale' => 'ar'])])
            ->where('journeys.2', ['key' => 'conception', 'url' => route('journeys.conception', ['locale' => 'ar'])])
            ->where('journeys.3', ['key' => 'doctor_prep', 'url' => route('journeys.doctor_prep', ['locale' => 'ar'])]));
    }

    public function test_landing_page_shares_quick_exit_and_public_page_links(): void
    {
        $this->get('/en')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('quickExitUrl', config('rafiq.quick_exit_url'))
            ->where('links.home', route('public.landing', ['locale' => 'en']))
            ->where('links.privacy', route('public.legal', ['locale' => 'en', 'page' => 'privacy']))
            ->where('links.sources', route('public.legal', ['locale' => 'en', 'page' => 'sources'])));
    }

    public function test_landing_records_aggregate_page_and_language_counters(): void
    {
        $this->get('/en')->assertOk();
        $this->get('/ar/welcome')->assertOk();

        $this->assertDatabaseHas('daily_metrics', ['metric' => 'page_view', 'dimension' => 'landing', 'value' => 1]);
        $this->assertDatabaseHas('daily_metrics', ['metric' => 'locale_used', 'dimension' => 'en', 'value' => 1]);
        $this->assertDatabaseHas('daily_metrics', ['metric' => 'page_view', 'dimension' => 'welcome', 'value' => 1]);
    }

    public function test_landing_page_declares_language_alternates_for_search_engines(): void
    {
        $response = $this->get('/ar');

        $response->assertSee('<link rel="canonical" href="'.route('public.landing', ['locale' => 'ar']).'">', false);
        $response->assertSee('<link rel="alternate" hreflang="ar" href="'.route('public.landing', ['locale' => 'ar']).'">', false);
        $response->assertSee('<link rel="alternate" hreflang="en" href="'.route('public.landing', ['locale' => 'en']).'">', false);
        $response->assertSee('<link rel="alternate" hreflang="x-default" href="'.route('public.landing', ['locale' => 'ar']).'">', false);
        $response->assertSee('<meta property="og:locale" content="ar_AR">', false);
        $response->assertSee(__('meta.description', [], 'ar'), false);
    }

    public function test_error_pages_are_not_indexed(): void
    {
        $this->get('/ar/missing-page')->assertNotFound()->assertSee('<meta name="robots" content="noindex">', false);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function legalPages(): array
    {
        return [
            'privacy ar' => ['ar', 'privacy'],
            'terms en' => ['en', 'terms'],
            'limits ar' => ['ar', 'limits'],
            'sources en' => ['en', 'sources'],
        ];
    }

    #[DataProvider('legalPages')]
    public function test_legal_pages_render_in_both_languages(string $locale, string $page): void
    {
        $this->get("/{$locale}/{$page}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->component('Public/Legal')
                ->where('page', $page)
                ->where('locale.current', $locale));
    }

    public function test_sources_page_lists_the_reference_register(): void
    {
        $this->get('/en/sources')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sources', 5)
            ->where('sources.0.id', 'SRC-001')
            ->where('sources.0.url', 'https://www.nhs.uk/conditions/testicle-pain/'));
    }

    public function test_language_switch_on_a_legal_page_keeps_the_same_page(): void
    {
        $this->get('/ar/privacy')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale.alternate.url', route('public.legal', ['locale' => 'en', 'page' => 'privacy'])));
    }

    public function test_unknown_legal_page_is_not_found(): void
    {
        $this->get('/en/cookies')->assertNotFound();
    }
}
