<?php

namespace Tests\Feature;

use App\Services\Clinical\RulesetImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Module-04/05/07: J2/J3 screening, evaluation and report through the deterministic engine.
 * Results derive from the draft ruleset (demo mode) and are pending qualified medical review.
 */
class AssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['rafiq.demo_mode' => true, 'rafiq.assessment_enabled' => true]);
        app(RulesetImporter::class)->import(resource_path('clinical/rulesets/RS-2026.1'));
        $this->withSession(['age_confirmed' => true, 'assessment_consent' => true]);
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    private function payload(array $answers, string $journey = 'concern'): array
    {
        return ['journey' => $journey, 'answers' => $answers];
    }

    private function noWarningSigns(): array
    {
        return [
            'ss_current_pain_or_swelling' => 'no',
            'ss_sudden_severe_pain' => 'not_applicable',
            'ss_pain_with_nausea_vomiting_abdominal' => 'not_applicable',
            'ss_pain_over_1h_or_at_rest' => 'not_applicable',
            'ss_fever_with_pain_or_swelling' => 'not_applicable',
            'ss_new_lump_or_change' => 'no',
        ];
    }

    public function test_the_journey_page_shows_the_ruleset_questions_in_the_visitors_language(): void
    {
        $this->get('/ar/concern')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Assessment')
            ->where('demo', true)
            ->where('questions.0.key', 'ss_current_pain_or_swelling')
            ->where('questions.0.prompt', 'هل لديك الآن ألم أو تورم في الخصية أو كيس الصفن؟')
            ->where('questions.0.options.0', ['value' => 'yes', 'label' => 'نعم'])
            ->where('questions.1.stop_on_yes', true));

        // J3 has the conception-only questions; J2 does not.
        $this->get('/en/conception')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('questions', fn ($questions): bool => collect($questions)->contains('key', 'as_trying_duration')));
        $this->get('/en/concern')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('questions', fn ($questions): bool => ! collect($questions)->contains('key', 'as_trying_duration')));
    }

    public function test_an_emergency_answer_is_screened_immediately_without_the_remaining_questions(): void
    {
        $this->postJson('/en/assessment/screen', $this->payload(['ss_current_pain_or_swelling' => 'yes', 'ss_sudden_severe_pain' => 'yes']))
            ->assertOk()
            ->assertJsonPath('status', 'emergency')
            ->assertJsonPath('report.priority', 'P0')
            ->assertJsonPath('report.label', 'Emergency')
            ->assertJsonPath('report.guidance.0', 'This may need urgent treatment in hospital. Call emergency services or go to the nearest emergency department now.')
            ->assertJsonPath('report.demo', true)
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

        $this->assertDatabaseHas('daily_metrics', ['metric' => 'assessment_started', 'dimension' => 'concern']);
    }

    public function test_a_fever_stops_the_assessment_with_same_day_advice(): void
    {
        $answers = ['ss_current_pain_or_swelling' => 'yes', 'ss_sudden_severe_pain' => 'no', 'ss_pain_with_nausea_vomiting_abdominal' => 'no', 'ss_pain_over_1h_or_at_rest' => 'no', 'ss_fever_with_pain_or_swelling' => 'yes', 'ss_new_lump_or_change' => 'no'];

        $this->postJson('/ar/assessment/screen', $this->payload($answers))
            ->assertJsonPath('status', 'urgent')
            ->assertJsonPath('report.priority', 'P1')
            ->assertJsonPath('report.label', 'عاجل');
    }

    public function test_no_warning_signs_passes_to_the_assessment_and_the_report_follows(): void
    {
        $this->postJson('/en/assessment/screen', $this->payload($this->noWarningSigns()))->assertJsonPath('status', 'passed');

        $this->postJson('/en/assessment/evaluate', $this->payload([...$this->noWarningSigns(), 'as_trying_duration' => 'gt_12m', 'as_smoking' => 'current'], 'conception'))
            ->assertOk()
            ->assertJsonPath('status', 'evaluated')
            ->assertJsonPath('report.priority', 'P2')
            ->assertJsonPath('report.guidance.0', 'After 12 months of regular trying without a pregnancy, a medical assessment for both partners is usually recommended. This is a routine step and does not necessarily mean there is a problem.')
            ->assertJsonPath('report.doctorTopics', fn (array $topics): bool => in_array('Assessment for both of us as a couple', $topics, true))
            ->assertJsonPath('report.notices.0', 'You skipped some questions, so this guidance may be incomplete.');

        $this->assertDatabaseHas('daily_metrics', ['metric' => 'assessment_completed', 'dimension' => 'conception']);
    }

    public function test_invalid_answers_are_rejected_without_a_result(): void
    {
        $this->postJson('/en/assessment/screen', $this->payload(['ss_current_pain_or_swelling' => 'maybe']))->assertStatus(422)->assertJsonPath('code', 'invalid_answers');
        $this->postJson('/en/assessment/evaluate', $this->payload([...$this->noWarningSigns(), 'as_trying_duration' => 'gt_12m']))->assertStatus(422);
        $this->postJson('/en/assessment/evaluate', ['journey' => 'concern', 'answers' => [], 'notes' => 'free text'])->assertStatus(422);
        $this->postJson('/en/assessment/evaluate', $this->payload(['ss_current_pain_or_swelling' => ['nested' => ['x']]]))->assertStatus(422);
    }

    public function test_consent_and_the_kill_switch_are_enforced(): void
    {
        $this->withSession(['age_confirmed' => true, 'assessment_consent' => false])
            ->postJson('/en/assessment/screen', $this->payload($this->noWarningSigns()))
            ->assertStatus(403)->assertJsonPath('code', 'consent_required');

        $this->withSession(['age_confirmed' => true, 'assessment_consent' => true]);
        config(['rafiq.assessment_enabled' => false]);
        $this->postJson('/en/assessment/screen', $this->payload($this->noWarningSigns()))->assertStatus(503)->assertJsonPath('code', 'assessment_unavailable');
        $this->get('/en/concern')->assertInertia(fn (AssertableInertia $page) => $page->where('questions', null));
    }

    public function test_without_an_allowed_ruleset_there_is_no_default_result(): void
    {
        config(['rafiq.demo_mode' => false]);

        $this->postJson('/en/assessment/screen', $this->payload($this->noWarningSigns()))->assertStatus(503);
        $this->get('/en/concern')->assertInertia(fn (AssertableInertia $page) => $page->where('questions', null));
    }

    public function test_answers_are_never_kept_in_the_session(): void
    {
        $this->postJson('/en/assessment/evaluate', $this->payload([...$this->noWarningSigns(), 'as_smoking' => 'current']))->assertOk();

        $this->assertSame([], array_diff(array_keys(session()->all()), config('rafiq.public_session_keys')));
        $this->assertDatabaseMissing('audit_logs', ['action' => 'assessment']);
    }
}
