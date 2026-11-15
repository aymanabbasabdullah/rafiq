<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Journey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires explicit consent to temporary processing of health answers before J2/J3 (FR-021, PRIV-013).
 *
 * Must run after EnsureAgeConfirmed.
 */
class EnsureAssessmentConsent
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('assessment_consent') === true) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['code' => 'consent_required'], 403);
        }

        return redirect()->route('gate.consent', ['journey' => $this->journeyFor($request)->value]);
    }

    /**
     * The consent page wording depends on the journey; it defaults to the concern journey.
     */
    private function journeyFor(Request $request): Journey
    {
        $routeName = (string) $request->route()?->getName();
        $journey = Journey::tryFrom(str_replace('journeys.', '', $routeName));

        return $journey !== null && $journey->requiresAssessmentConsent() ? $journey : Journey::Concern;
    }
}
