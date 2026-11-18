<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\AiMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\AiMessageRequest;
use App\Services\Ai\AiSettings;
use App\Services\Ai\CompanionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

/**
 * Rafiq AI companion endpoints (Module-09). JSON only; nothing is stored about the conversation.
 */
class AiController extends Controller
{
    public function message(AiMessageRequest $request, CompanionService $companion, AiSettings $settings): JsonResponse
    {
        if (! $settings->enabled()) {
            return response()->json(['code' => 'ai_disabled'], 503);
        }

        $locale = App::getLocale();

        $reply = $companion->reply(
            mode: AiMode::from((string) $request->validated('mode')),
            locale: $locale,
            message: (string) $request->validated('message'),
            history: array_values((array) $request->validated('history', [])),
            articleId: $request->validated('article_id') !== null ? (int) $request->validated('article_id') : null,
            termId: $request->validated('term_id') !== null ? (int) $request->validated('term_id') : null,
            preset: $request->validated('preset'),
            simplification: $request->validated('simplification') !== null ? (int) $request->validated('simplification') : null,
        );

        return response()->json(['reply' => $reply->toArray(), 'disclaimer' => __('ai.disclaimer', [], $locale)]);
    }

    /**
     * Approved welcome text and suggested questions for a page context (M09-FR-08). No model call.
     */
    public function suggestions(Request $request, AiSettings $settings): JsonResponse
    {
        $validated = $request->validate(['context' => ['required', Rule::in(AiSettings::SUGGESTION_CONTEXTS)]]);
        $locale = App::getLocale();

        return response()->json([
            'enabled' => $settings->enabled(),
            'welcome' => $settings->welcome($locale),
            'suggestions' => $settings->suggestions($validated['context'], $locale),
        ]);
    }
}
