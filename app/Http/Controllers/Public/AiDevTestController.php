<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\AiMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\AiMessageRequest;
use App\Services\Ai\CompanionService;
use App\Services\Ai\Contracts\AiTextGenerator;
use App\Services\Ai\Gemini\GeminiTextGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;

/**
 * Development-only end-to-end test of the real assistant pipeline (Postman), without switching the
 * assistant on for visitors.
 *
 * Same request validation, red-flag interception, pre-filter, approved-content grounding, post-filter
 * and usage logging as POST /{locale}/ai/messages; same age gate, AI notice, CSRF and rate limits.
 * Differences: it ignores the global RAFIQ_AI_ENABLED switch, always uses the real Gemini provider
 * (never the fake), accepts platform_guide mode only, and adds diagnostics (never the API key).
 */
class AiDevTestController extends Controller
{
    public function message(AiMessageRequest $request): JsonResponse
    {
        abort_unless(App::environment(['local', 'testing']) && config('rafiq.ai.dev_test_route'), 404);

        if ($request->validated('mode') !== AiMode::PlatformGuide->value) {
            return response()->json(['message' => 'The development test route only accepts mode "platform_guide".', 'errors' => ['mode' => ['platform_guide_only']]], 422);
        }

        // Force the real provider for this request only: a missing key fails visibly instead of using the fake.
        app()->instance(AiTextGenerator::class, GeminiTextGenerator::fromConfig());
        $locale = App::getLocale();

        $reply = app(CompanionService::class)->reply(
            mode: AiMode::PlatformGuide,
            locale: $locale,
            message: (string) $request->validated('message'),
            history: array_values((array) $request->validated('history', [])),
            simplification: $request->validated('simplification') !== null ? (int) $request->validated('simplification') : null,
        );

        return response()->json([
            'reply' => $reply->toArray(),
            'disclaimer' => __('ai.disclaimer', [], $locale),
            'meta' => [
                'route' => 'development-test',
                'provider' => 'gemini',
                'configured_model' => config('services.gemini.model') ?: null,
                // True only when an HTTP request to Google was actually made (a missing key makes none).
                'model_called' => $reply->model !== null && ! str_starts_with((string) $reply->diagnostic, 'not_configured'),
                'model' => $reply->model,
                'latency_ms' => $reply->latencyMs,
                'diagnostic' => $reply->diagnostic,
                'assistant_enabled_for_visitors' => (bool) config('rafiq.ai.enabled'),
            ],
        ]);
    }
}
