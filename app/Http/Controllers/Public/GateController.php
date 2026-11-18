<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\Journey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreAgeGateRequest;
use App\Http\Requests\Public\StoreConsentRequest;
use App\Services\Analytics\MetricsRecorder;
use App\Support\GateRedirects;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Age gate, assessment consent and AI notice (FR-020..FR-022).
 *
 * Each gate stores a single boolean in the session; nothing identifying is recorded.
 */
class GateController extends Controller
{
    public function showAge(Request $request): Response
    {
        return Inertia::render('Public/Gate/Age', [
            'intended' => GateRedirects::allowed($request->query('intended') !== null ? (string) $request->query('intended') : null),
        ]);
    }

    public function storeAge(StoreAgeGateRequest $request, MetricsRecorder $metrics): RedirectResponse
    {
        $isAdult = $request->boolean('is_adult');

        $request->session()->put('age_confirmed', $isAdult);
        $metrics->increment('age_gate', $isAdult ? 'adult' : 'under18');

        if (! $isAdult) {
            return redirect()->route('public.under18');
        }

        return redirect()->to(GateRedirects::urlFor($request->validated('intended')));
    }

    public function showConsent(Request $request): Response
    {
        $journey = Journey::tryFrom((string) $request->query('journey'));

        if ($journey === null || ! $journey->requiresAssessmentConsent()) {
            $journey = Journey::Concern;
        }

        return Inertia::render('Public/Gate/Consent', [
            'journey' => $journey->value,
            'submitUrl' => route('gate.consent.store'),
            'declineUrl' => GateRedirects::urlFor('journeys.learn'),
        ]);
    }

    public function storeConsent(StoreConsentRequest $request): RedirectResponse
    {
        $request->session()->put('assessment_consent', true);

        $destination = 'journeys.'.$request->validated('journey');

        return redirect()->to(GateRedirects::urlFor($destination));
    }

    public function storeAiNotice(Request $request): JsonResponse
    {
        $request->session()->put('ai_notice_ack', true);

        return response()->json(['acknowledged' => true]);
    }
}
