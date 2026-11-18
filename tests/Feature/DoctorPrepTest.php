<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Models\DoctorQuestionTemplateTranslation;
use Database\Seeders\DoctorPrepDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Module-10: approved question bank per language, drafts only in demo mode, and no endpoint that could
 * receive a visitor's own questions (BR-10-1).
 */
class DoctorPrepTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DoctorPrepDemoSeeder::class);
        $this->withSession(['age_confirmed' => true]);
    }

    public function test_demo_mode_shows_the_draft_bank_in_the_visitors_language_with_a_draft_notice(): void
    {
        config(['rafiq.demo_mode' => true]);

        $this->get('/ar/doctor-prep')
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/DoctorPrep')
                ->where('demo', true)
                ->has('topics', 6)
                ->where('topics.0.key', 'pain_swelling')
                ->where('topics.0.label', 'الألم أو التورم')
                ->has('topics.0.questions', 5)
                ->where('topics.0.questions.0.text', 'ما الذي قد يسبب الألم أو التورم الذي لاحظته؟')
                ->has('bring', 6));

        $this->assertDatabaseHas('daily_metrics', ['metric' => 'page_view', 'dimension' => 'doctor_prep']);
    }

    public function test_outside_demo_mode_only_medically_approved_items_are_shown(): void
    {
        config(['rafiq.demo_mode' => false]);

        $this->get('/en/doctor-prep')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('demo', false)
            ->has('topics', 0)
            ->has('bring', 0));

        DoctorQuestionTemplateTranslation::query()
            ->where('locale', 'en')
            ->whereHas('template', fn ($query) => $query->whereIn('key', ['question.fertility.1', 'bring.1']))
            ->update(['review_status' => ReviewStatus::Approved]);
        DoctorQuestionTemplateTranslation::query()
            ->where('locale', 'en')
            ->whereHas('template', fn ($query) => $query->where('key', 'bring.1'))
            ->update(['publish_status' => PublishStatus::Hidden]);

        $this->get('/en/doctor-prep')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('topics', 1)
            ->where('topics.0.key', 'fertility')
            ->has('topics.0.questions', 1)
            ->has('bring', 0));
    }

    public function test_the_page_needs_the_age_gate_but_no_assessment_consent(): void
    {
        $this->flushSession();
        $this->get('/en/doctor-prep')->assertRedirect(route('gate.age', ['locale' => 'en', 'intended' => 'journeys.doctor_prep']));
    }

    public function test_no_route_can_receive_the_visitors_own_questions(): void
    {
        foreach (Route::getRoutes() as $route) {
            $writes = array_diff($route->methods(), ['GET', 'HEAD']) !== [];

            $this->assertFalse(
                $writes && (str_contains($route->uri(), 'doctor') || str_contains((string) $route->getName(), 'doctor_prep')),
                "Unexpected write route for doctor prep: {$route->uri()}",
            );
        }
    }
}
