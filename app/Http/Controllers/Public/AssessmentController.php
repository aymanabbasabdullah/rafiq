<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\AssessmentRequest;
use App\Services\Analytics\MetricsRecorder;
use App\Services\Assessment\GuidanceService;
use App\Services\Clinical\ActiveRulesetResolver;
use App\Services\Clinical\ClinicalRulesEngine;
use App\Services\Clinical\Data\AssessmentInput;
use App\Services\Clinical\Exceptions\InvalidAssessmentInput;
use App\Services\Clinical\Exceptions\NoActiveRuleset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use RuntimeException;

/**
 * J2/J3 safety screening and evaluation (Module-04, Module-05, Module-07).
 *
 * The deterministic Clinical Rules Engine decides; the AI is never involved. Answers come from the
 * browser's memory, are evaluated and discarded: nothing is stored or logged (PRIV-002). Only aggregate
 * counters without results are recorded (M04-FR-09, M05-FR-09).
 */
class AssessmentController extends Controller
{
    public function __construct(
        private readonly ActiveRulesetResolver $resolver,
        private readonly ClinicalRulesEngine $engine,
        private readonly GuidanceService $guidance,
        private readonly MetricsRecorder $metrics,
    ) {}

    public function screen(AssessmentRequest $request): JsonResponse
    {
        return $this->run($request, screening: true);
    }

    public function evaluate(AssessmentRequest $request): JsonResponse
    {
        return $this->run($request, screening: false);
    }

    private function run(AssessmentRequest $request, bool $screening): JsonResponse
    {
        if (! config('rafiq.assessment_enabled')) {
            return $this->unavailable();
        }

        $journey = (string) $request->validated('journey');
        $input = new AssessmentInput($journey, $request->session()->get('age_confirmed') === true, $request->answers());

        try {
            $ruleset = $this->resolver->resolve();
            $result = $screening ? $this->engine->screen($ruleset, $input) : $this->engine->evaluate($ruleset, $input);
            $report = $this->guidance->build($result, App::getLocale());
        } catch (InvalidAssessmentInput $exception) {
            return response()->json(['code' => 'invalid_answers', 'errors' => $exception->errors], 422);
        } catch (NoActiveRuleset|RuntimeException) {
            // Never a default or partial result (BR-06-4, M07-FR-02).
            return $this->unavailable();
        }

        $this->metrics->increment($screening ? 'assessment_started' : 'assessment_completed', $journey);

        return response()->json([
            'status' => $screening ? $this->screeningStatus($report['priority'], $result->stopped) : 'evaluated',
            'report' => $report,
        ]);
    }

    /**
     * P0 (or GATE) stops; P1 stops the assessment with same-day advice (M04-FR-06); anything else continues.
     */
    private function screeningStatus(?string $priority, bool $stopped): string
    {
        return match (true) {
            $priority === 'GATE' => 'gate',
            $stopped => 'emergency',
            $priority === 'P1' => 'urgent',
            default => 'passed',
        };
    }

    private function unavailable(): JsonResponse
    {
        return response()->json(['code' => 'assessment_unavailable', 'message' => __('safety.general')], 503);
    }
}
