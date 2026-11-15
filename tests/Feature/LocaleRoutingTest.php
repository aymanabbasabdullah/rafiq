<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LocaleRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_the_preferred_supported_language(): void
    {
        $this->get('/', ['Accept-Language' => 'en-GB,en;q=0.9'])
            ->assertRedirect(route('public.landing', ['locale' => 'en']));
    }

    public function test_root_redirects_to_arabic_when_the_browser_language_is_unsupported(): void
    {
        $this->get('/', ['Accept-Language' => 'fr-FR,fr;q=0.9'])
            ->assertRedirect(route('public.landing', ['locale' => 'ar']));
    }

    public function test_root_prefers_the_language_already_chosen_in_the_session(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/', ['Accept-Language' => 'ar'])
            ->assertRedirect(route('public.landing', ['locale' => 'en']));
    }

    public function test_unsupported_language_prefix_returns_not_found(): void
    {
        $this->get('/fr')->assertNotFound();
    }

    public function test_arabic_pages_render_right_to_left(): void
    {
        $response = $this->get('/ar');

        $response->assertOk();
        $response->assertSee('<html lang="ar" dir="rtl">', false);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Landing')
            ->where('locale.current', 'ar')
            ->where('locale.direction', 'rtl')
            ->where('locale.alternate.code', 'en')
            ->where('locale.alternate.url', route('public.landing', ['locale' => 'en']))
            ->where('translations', fn ($translations): bool => $translations['app.name'] === 'رفيق'));
    }

    public function test_english_pages_render_left_to_right(): void
    {
        $response = $this->get('/en');

        $response->assertOk();
        $response->assertSee('<html lang="en" dir="ltr">', false);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale.direction', 'ltr')
            ->where('locale.alternate.code', 'ar')
            ->where('translations', fn ($translations): bool => $translations['app.name'] === 'Rafiq'));
    }

    public function test_visiting_a_language_remembers_it_in_the_session(): void
    {
        $this->get('/en')->assertSessionHas('locale', 'en');
    }

    public function test_demo_mode_flag_is_shared_with_pages(): void
    {
        config(['rafiq.demo_mode' => true]);

        $this->get('/ar')->assertInertia(fn (AssertableInertia $page) => $page->where('demoMode', true));
    }

    public function test_emergency_number_is_null_until_a_country_is_configured(): void
    {
        $this->get('/ar')->assertInertia(fn (AssertableInertia $page) => $page->where('emergency.number', null));
    }
}
