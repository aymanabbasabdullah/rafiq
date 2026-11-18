<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Module-02: "Choose your journey" page, journey entry pages, gate matrix, feature flags and counters.
 */
class JourneysTest extends TestCase
{
    use RefreshDatabase;

    public function test_journeys_page_renders_all_four_journeys_in_both_languages_without_requiring_a_gate(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $this->get("/{$locale}/journeys")
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->component('Public/Journeys')
                    ->has('journeys', 4)
                    ->where('journeys.0', ['key' => 'learn', 'url' => route('journeys.learn', ['locale' => $locale])])
                    ->where('journeys.1', ['key' => 'concern', 'url' => route('journeys.concern', ['locale' => $locale])])
                    ->where('journeys.2', ['key' => 'conception', 'url' => route('journeys.conception', ['locale' => $locale])])
                    ->where('journeys.3', ['key' => 'doctor_prep', 'url' => route('journeys.doctor_prep', ['locale' => $locale])])
                    ->where('concernUrl', route('journeys.concern', ['locale' => $locale]))
                    ->where('links.journeys', route('journeys.index', ['locale' => $locale])));
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function journeyRoutes(): array
    {
        return [
            'J1 learn' => ['/ar/learn', 'journeys.learn'],
            'J2 concern' => ['/ar/concern', 'journeys.concern'],
            'J3 conception' => ['/ar/conception', 'journeys.conception'],
            'J4 doctor prep' => ['/ar/doctor-prep', 'journeys.doctor_prep'],
        ];
    }

    #[DataProvider('journeyRoutes')]
    public function test_every_journey_requires_the_age_gate_first(string $path, string $routeName): void
    {
        $this->get($path)
            ->assertRedirect(route('gate.age', ['locale' => 'ar', 'intended' => $routeName]));
    }

    #[DataProvider('journeyRoutes')]
    public function test_visitors_under_eighteen_are_redirected_away_from_every_journey(string $path, string $routeName): void
    {
        $this->withSession(['age_confirmed' => false])
            ->get($path)
            ->assertRedirect(route('public.under18', ['locale' => 'ar']));
    }

    public function test_learn_journey_opens_after_age_confirmation_with_no_consent_questionnaire_and_shows_red_flag_reminder(): void
    {
        $this->withSession(['age_confirmed' => true])
            ->get('/ar/learn')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                // Module-08 replaces the J1 placeholder with the Knowledge Center home.
                ->component('Public/Learn/Index')
                ->where('concernUrl', route('journeys.concern', ['locale' => 'ar']))
                ->has('otherJourneys', 3)
                ->where('otherJourneys.0.key', 'concern')
                ->where('otherJourneys.1.key', 'conception')
                ->where('otherJourneys.2.key', 'doctor_prep'));
    }

    public function test_doctor_prep_journey_requires_no_assessment_consent_shows_local_only_notice_and_is_never_cached(): void
    {
        $this->withSession(['age_confirmed' => true])
            ->get('/en/doctor-prep')
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/DoctorPrep')
                ->where('concernUrl', route('journeys.concern', ['locale' => 'en']))
                ->has('otherJourneys', 3)
                ->where('otherJourneys.0.key', 'learn')
                ->where('otherJourneys.1.key', 'concern')
                ->where('otherJourneys.2.key', 'conception'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function consentJourneys(): array
    {
        return [
            'J2 concern' => ['/ar/concern', 'concern'],
            'J3 conception' => ['/ar/conception', 'conception'],
        ];
    }

    #[DataProvider('consentJourneys')]
    public function test_assessment_journeys_require_consent_and_send_no_store_headers(string $path, string $journey): void
    {
        $this->withSession(['age_confirmed' => true])
            ->get($path)
            ->assertRedirect(route('gate.consent', ['locale' => 'ar', 'journey' => $journey]));

        $this->withSession(['age_confirmed' => true, 'assessment_consent' => true])
            ->get($path)
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Assessment')
                ->where('journey', $journey)
                ->has('otherJourneys', 3));
    }

    public function test_full_gate_flow_hands_off_to_the_chosen_assessment_journey(): void
    {
        $this->get('/ar/concern')
            ->assertRedirect(route('gate.age', ['locale' => 'ar', 'intended' => 'journeys.concern']));

        $this->post('/ar/gate/age', ['is_adult' => true, 'intended' => 'journeys.concern'])
            ->assertRedirect(route('journeys.concern', ['locale' => 'ar']));

        $this->get('/ar/concern')
            ->assertRedirect(route('gate.consent', ['locale' => 'ar', 'journey' => 'concern']));

        $this->post('/ar/gate/consent', ['journey' => 'concern', 'accepted' => true])
            ->assertRedirect(route('journeys.concern', ['locale' => 'ar']));

        $this->get('/ar/concern')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Assessment')
                ->where('journey', 'concern'));
    }

    public function test_declining_assessment_consent_leads_to_the_learn_journey(): void
    {
        $this->withSession(['age_confirmed' => true])
            ->get('/ar/gate/consent?journey=concern')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('declineUrl', route('journeys.learn', ['locale' => 'ar'])));

        $this->withSession(['age_confirmed' => true])
            ->get('/ar/learn')
            ->assertOk();
    }

    public function test_journey_selected_counter_is_recorded_once_per_session_for_each_journey(): void
    {
        $this->withSession(['age_confirmed' => true]);

        $this->get('/ar/learn')->assertOk();
        $this->get('/ar/learn')->assertOk();
        $this->get('/en/learn')->assertOk();

        $this->assertDatabaseHas('daily_metrics', [
            'metric' => 'journey_selected',
            'dimension' => 'learn',
            'value' => 1,
        ]);

        $this->get('/ar/doctor-prep')->assertOk();
        $this->get('/ar/doctor-prep')->assertOk();

        $this->assertDatabaseHas('daily_metrics', [
            'metric' => 'journey_selected',
            'dimension' => 'doctor_prep',
            'value' => 1,
        ]);
    }

    public function test_disabled_journey_is_hidden_from_cards_and_returns_not_found(): void
    {
        config(['rafiq.journeys.enabled.conception' => false]);

        $this->get('/ar')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('journeys', 3)
            ->where('journeys.0.key', 'learn')
            ->where('journeys.1.key', 'concern')
            ->where('journeys.2.key', 'doctor_prep'));

        $this->get('/ar/journeys')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('journeys', 3));

        $this->withSession(['age_confirmed' => true])
            ->get('/ar/learn')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('otherJourneys', 2)
                ->where('otherJourneys.0.key', 'concern')
                ->where('otherJourneys.1.key', 'doctor_prep'));

        $this->get('/ar/conception')->assertNotFound();

        $this->withSession(['age_confirmed' => true, 'assessment_consent' => true])
            ->get('/ar/conception')
            ->assertNotFound();

        $this->get('/ar/gate/age?intended=journeys.conception')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('intended', null));
    }

    public function test_sensitive_journey_pages_encrypt_history_in_secure_contexts_and_prevent_indexing(): void
    {
        $this->withSession(['age_confirmed' => true, 'assessment_consent' => true]);

        foreach (['/ar/concern', '/ar/conception', '/ar/doctor-prep'] as $path) {
            $response = $this->get("https://rafiq.test{$path}");
            $response->assertOk()->assertSee('<meta name="robots" content="noindex">', false);
            $this->assertTrue($response->viewData('page')['encryptHistory'] ?? false);
        }

        // Module-08: J1 is now the public Knowledge Center, which is meant to be indexable (OQ-035).
        $this->get('/ar/learn')->assertDontSee('<meta name="robots" content="noindex">', false);
    }

    public function test_language_switcher_preserves_the_current_journey_page(): void
    {
        $this->get('/ar/journeys')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('locale.alternate.url', route('journeys.index', ['locale' => 'en'])));

        $this->withSession(['age_confirmed' => true])
            ->get('/ar/doctor-prep')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('locale.alternate.url', route('journeys.doctor_prep', ['locale' => 'en'])));
    }

    public function test_session_contains_only_allowed_keys_after_entering_all_journeys(): void
    {
        $this->post('/ar/gate/age', ['is_adult' => true]);
        $this->post('/ar/gate/consent', ['journey' => 'concern', 'accepted' => true]);

        $this->get('/ar/journeys')->assertOk();
        $this->get('/ar/learn')->assertOk();
        $this->get('/ar/concern')->assertOk();
        $this->get('/ar/conception')->assertOk();
        $this->get('/ar/doctor-prep')->assertOk();

        $unexpectedKeys = array_diff(array_keys(session()->all()), config('rafiq.public_session_keys'));

        $this->assertSame([], array_values($unexpectedKeys));
    }
}
