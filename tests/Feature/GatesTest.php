<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Module-03: age gate, assessment consent, AI notice and "Clear everything".
 *
 * Journey pages arrive in Module-02, so stand-in routes with the real names are registered here.
 */
class GatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', SetLocale::class, 'age'])
            ->prefix('{locale}')
            ->group(function (): void {
                Route::get('/__test/learn', fn () => 'learn')->name('journeys.learn');
                Route::get('/__test/concern', fn () => 'concern')->middleware('consent')->name('journeys.concern');
                Route::get('/__test/conception', fn () => 'conception')->middleware('consent')->name('journeys.conception');
                Route::post('/__test/ai', fn () => response()->json(['ok' => true]))->middleware('ai-notice')->name('test.ai');
            });

        Route::getRoutes()->refreshNameLookups();
    }

    public function test_age_gate_page_keeps_only_allowed_destinations(): void
    {
        $this->get('/ar/gate/age?intended=journeys.concern')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Gate/Age')->where('intended', 'journeys.concern'));

        $this->get('/ar/gate/age?intended=https://evil.example')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('intended', null));
    }

    public function test_confirming_adulthood_unlocks_journeys_and_records_an_aggregate_counter(): void
    {
        $this->post('/ar/gate/age', ['is_adult' => true])
            ->assertRedirect(route('public.landing', ['locale' => 'ar']))
            ->assertSessionHas('age_confirmed', true);

        $this->assertDatabaseHas('daily_metrics', ['metric' => 'age_gate', 'dimension' => 'adult', 'value' => 1]);
    }

    public function test_confirming_adulthood_returns_to_the_intended_journey(): void
    {
        $this->post('/en/gate/age', ['is_adult' => true, 'intended' => 'journeys.learn'])
            ->assertRedirect(route('journeys.learn', ['locale' => 'en']));
    }

    public function test_unknown_destinations_are_rejected_to_prevent_open_redirects(): void
    {
        $this->post('/ar/gate/age', ['is_adult' => true, 'intended' => 'https://evil.example'])
            ->assertSessionHasErrors('intended')
            ->assertSessionMissing('age_confirmed');
    }

    public function test_a_birth_date_is_never_accepted_instead_of_a_yes_or_no(): void
    {
        $this->post('/ar/gate/age', ['is_adult' => '1990-01-01'])->assertSessionHasErrors('is_adult');
    }

    public function test_visitors_under_eighteen_are_sent_to_the_safe_page(): void
    {
        $this->post('/ar/gate/age', ['is_adult' => false])
            ->assertRedirect(route('public.under18', ['locale' => 'ar']))
            ->assertSessionHas('age_confirmed', false);

        $this->assertDatabaseHas('daily_metrics', ['metric' => 'age_gate', 'dimension' => 'under18', 'value' => 1]);

        $this->get('/ar/under-18')
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Under18')
                ->where('ageGateUrl', route('gate.age', ['locale' => 'ar'])));
    }

    public function test_journeys_require_the_age_gate_first(): void
    {
        $this->get('/en/__test/learn')
            ->assertRedirect(route('gate.age', ['locale' => 'en', 'intended' => 'journeys.learn']));
    }

    public function test_visitors_under_eighteen_cannot_open_journeys(): void
    {
        $this->withSession(['age_confirmed' => false])
            ->get('/en/__test/learn')
            ->assertRedirect(route('public.under18', ['locale' => 'en']));
    }

    public function test_json_requests_get_machine_readable_gate_errors(): void
    {
        $this->getJson('/en/__test/learn')->assertForbidden()->assertJson(['code' => 'age_required']);

        $this->withSession(['age_confirmed' => false])
            ->getJson('/en/__test/learn')
            ->assertForbidden()
            ->assertJson(['code' => 'age_ineligible']);
    }

    public function test_confirmed_adults_reach_journeys_that_need_no_consent(): void
    {
        $this->withSession(['age_confirmed' => true])->get('/en/__test/learn')->assertOk();
    }

    public function test_assessment_journeys_require_consent(): void
    {
        $this->withSession(['age_confirmed' => true])
            ->get('/ar/__test/conception')
            ->assertRedirect(route('gate.consent', ['locale' => 'ar', 'journey' => 'conception']));

        $this->withSession(['age_confirmed' => true])
            ->getJson('/ar/__test/concern')
            ->assertForbidden()
            ->assertJson(['code' => 'consent_required']);
    }

    public function test_consent_page_requires_the_age_gate_and_is_never_cached(): void
    {
        $this->get('/ar/gate/consent?journey=concern')->assertRedirect();

        $this->withSession(['age_confirmed' => true])
            ->get('/ar/gate/consent?journey=conception')
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Gate/Consent')
                ->where('journey', 'conception')
                ->where('submitUrl', route('gate.consent.store', ['locale' => 'ar']))
                ->where('declineUrl', route('journeys.learn', ['locale' => 'ar'])));
    }

    public function test_consent_page_falls_back_to_the_concern_wording_for_other_journeys(): void
    {
        $this->withSession(['age_confirmed' => true])
            ->get('/ar/gate/consent?journey=learn')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('journey', 'concern'));
    }

    public function test_consent_must_be_explicitly_ticked(): void
    {
        $this->withSession(['age_confirmed' => true])
            ->post('/ar/gate/consent', ['journey' => 'concern'])
            ->assertSessionHasErrors('accepted')
            ->assertSessionMissing('assessment_consent');
    }

    public function test_giving_consent_opens_the_chosen_journey(): void
    {
        $this->withSession(['age_confirmed' => true])
            ->post('/ar/gate/consent', ['journey' => 'concern', 'accepted' => true])
            ->assertRedirect(route('journeys.concern', ['locale' => 'ar']))
            ->assertSessionHas('assessment_consent', true);

        $this->get('/ar/__test/concern')->assertOk();
    }

    public function test_ai_notice_must_be_acknowledged_before_any_ai_request(): void
    {
        $this->withSession(['age_confirmed' => true])
            ->postJson('/en/__test/ai')
            ->assertStatus(409)
            ->assertJson(['code' => 'ai_notice_required']);

        $this->postJson('/en/gate/ai-notice')->assertOk()->assertJson(['acknowledged' => true]);

        $this->postJson('/en/__test/ai')->assertOk();
    }

    public function test_ai_notice_cannot_be_acknowledged_before_the_age_gate(): void
    {
        $this->postJson('/en/gate/ai-notice')->assertForbidden()->assertJson(['code' => 'age_required']);
    }

    public function test_gates_status_is_shared_with_pages(): void
    {
        $this->withSession(['age_confirmed' => true, 'ai_notice_ack' => true])
            ->get('/en')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('gates.ageConfirmed', true)
                ->where('gates.assessmentConsent', false)
                ->where('gates.aiNoticeAcknowledged', true)
                ->where('links.clearSession', route('session.clear', ['locale' => 'en'])));
    }

    public function test_clear_everything_ends_the_session_and_clears_browser_history(): void
    {
        $this->withSession(['age_confirmed' => true, 'assessment_consent' => true, 'ai_notice_ack' => true]);
        $tokenBefore = session()->token();

        $this->post('/ar/session/clear')->assertRedirect(route('public.landing', ['locale' => 'ar']));

        $this->assertNull(session('age_confirmed'));
        $this->assertNull(session('assessment_consent'));
        $this->assertNull(session('ai_notice_ack'));
        $this->assertNotSame($tokenBefore, session()->token());

        $page = $this->get('/ar')->viewData('page');
        $this->assertTrue($page['clearHistory'] ?? false);
    }

    public function test_history_is_encrypted_on_sensitive_pages_in_secure_contexts_only(): void
    {
        $this->withSession(['age_confirmed' => true]);

        $secure = $this->get('https://rafiq.test/ar/gate/consent')->viewData('page');
        $this->assertTrue($secure['encryptHistory'] ?? false);

        $plainHttp = $this->get('http://rafiq.test/ar/gate/consent')->viewData('page');
        $this->assertArrayNotHasKey('encryptHistory', $plainHttp);
    }

    public function test_session_holds_only_allowed_keys_after_passing_every_gate(): void
    {
        $this->post('/ar/gate/age', ['is_adult' => true]);
        $this->post('/ar/gate/consent', ['journey' => 'concern', 'accepted' => true]);
        $this->postJson('/ar/gate/ai-notice');
        $this->post('/ar/session/clear');
        $this->post('/ar/gate/age', ['is_adult' => true]);

        $unexpectedKeys = array_diff(array_keys(session()->all()), config('rafiq.public_session_keys'));

        $this->assertSame([], array_values($unexpectedKeys));
    }
}
