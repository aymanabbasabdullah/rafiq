<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\Journey;
use App\Http\Controllers\Controller;
use App\Services\Ai\AiSettings;
use App\Services\Analytics\MetricsRecorder;
use App\Services\Assessment\QuestionnaireBuilder;
use App\Services\Clinical\ActiveRulesetResolver;
use App\Services\Clinical\Exceptions\NoActiveRuleset;
use App\Services\DoctorPrep\DoctorPrepBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Journey selection page and the entry pages for J1–J4 (FR-010..FR-014, Module-02).
 */
class JourneyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Public/Journeys', [
            'journeys' => Journey::cards(),
            'concernUrl' => Journey::Concern->url(),
        ]);
    }

    public function enter(Request $request, MetricsRecorder $metrics): Response
    {
        $journey = Journey::fromRouteName($request->route()?->getName());

        abort_if($journey === null || ! $journey->isEnabled(), 404);

        $selectedKey = "journeys_selected.{$journey->value}";

        if ($request->session()->get($selectedKey) !== true) {
            $request->session()->put($selectedKey, true);
            $metrics->increment('journey_selected', $journey->value);
        }

        if ($journey->requiresAssessmentConsent()) {
            return $this->assessment($journey);
        }

        if ($journey === Journey::DoctorPrep) {
            return $this->doctorPrep($metrics);
        }

        return Inertia::render('Public/JourneyEntry', [
            'journey' => $journey->value,
            'showsRedFlagReminder' => $journey->showsRedFlagReminder(),
            'showsLocalOnlyNotice' => $journey->showsLocalOnlyNotice(),
            'concernUrl' => Journey::Concern->url(),
            'otherJourneys' => Journey::cards(exclude: $journey),
        ]);
    }

    /**
     * J4: doctor visit preparation (Module-10). The visitor's list is built and printed in the browser only.
     */
    private function doctorPrep(MetricsRecorder $metrics): Response
    {
        $locale = App::getLocale();
        $demo = (bool) config('rafiq.demo_mode') && ! app()->isProduction();
        $metrics->increment('page_view', 'doctor_prep');

        return Inertia::render('Public/DoctorPrep', [
            ...app(DoctorPrepBank::class)->forLocale($locale, includeDrafts: $demo),
            'demo' => $demo,
            'concernUrl' => Journey::Concern->url(),
            'glossaryUrl' => Journey::Learn->isEnabled() ? route('glossary.index', ['locale' => $locale]) : null,
            'aiEnabled' => app(AiSettings::class)->enabled(),
            'otherJourneys' => Journey::cards(exclude: Journey::DoctorPrep),
        ]);
    }

    /**
     * J2/J3: the guided assessment (M04/M05). Questions come from the ruleset allowed in this environment;
     * when none may run, or the kill switch is off, the page shows the general safety message instead.
     */
    private function assessment(Journey $journey): Response
    {
        $locale = App::getLocale();
        $questions = null;
        $demo = false;

        if (config('rafiq.assessment_enabled')) {
            try {
                $ruleset = app(ActiveRulesetResolver::class)->resolve();
                $questions = app(QuestionnaireBuilder::class)->questions($ruleset, $journey->value, $locale);
                $demo = $ruleset->demo;
            } catch (NoActiveRuleset) {
                $questions = null;
            }
        }

        return Inertia::render('Public/Assessment', [
            'journey' => $journey->value,
            'questions' => $questions,
            'demo' => $demo,
            'screenUrl' => route('assessment.screen', ['locale' => $locale]),
            'evaluateUrl' => route('assessment.evaluate', ['locale' => $locale]),
            'feedbackUrl' => route('report.feedback', ['locale' => $locale]),
            'learnUrl' => Journey::Learn->url(),
            'doctorPrepUrl' => Journey::DoctorPrep->url(),
            'otherJourneys' => Journey::cards(exclude: $journey),
        ]);
    }
}
