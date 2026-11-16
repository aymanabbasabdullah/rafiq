<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiMode;
use App\Enums\AiOutcome;
use App\Models\AiUsageLog;
use App\Services\Ai\Data\AiResult;
use App\Services\Analytics\MetricsRecorder;

/**
 * Records metadata only (AI-017, GR-14): never text, chunks, IPs, sessions or user agents.
 * The timestamp is rounded to the minute.
 */
final class UsageLogger
{
    public function __construct(private readonly MetricsRecorder $metrics, private readonly AiSettings $settings) {}

    public function record(AiMode $mode, string $locale, AiOutcome $outcome, ?AiResult $result = null, ?int $latencyMs = null, ?string $errorCode = null, ?int $articleId = null, ?string $model = null): void
    {
        AiUsageLog::query()->create([
            'created_at' => now()->startOfMinute(),
            'mode' => $mode,
            'locale' => $locale,
            'outcome' => $outcome,
            'ai_configuration_version' => $this->settings->version(),
            'core_prompt_version' => CorePrompt::VERSION,
            'model' => $result->model ?? $model,
            'input_tokens' => $result?->inputTokens,
            'output_tokens' => $result?->outputTokens,
            'latency_ms' => $latencyMs,
            'error_code' => $errorCode,
            'estimated_cost_micros' => $result !== null ? $this->costMicros($result) : null,
            'article_id' => $articleId,
        ]);

        $this->metrics->increment('ai_request', $outcome->value);

        if ($mode === AiMode::ExplainArticle && $outcome === AiOutcome::Answered) {
            $this->metrics->increment('article_ai_explain', $locale);
        }
    }

    private function costMicros(AiResult $result): ?int
    {
        $input = (float) config('rafiq.ai.pricing.input_per_million_usd');
        $output = (float) config('rafiq.ai.pricing.output_per_million_usd');

        if (($input === 0.0 && $output === 0.0) || $result->inputTokens === null) {
            return null;
        }

        // USD per million tokens × tokens = micro-dollars.
        return (int) round($result->inputTokens * $input + (int) $result->outputTokens * $output);
    }
}
