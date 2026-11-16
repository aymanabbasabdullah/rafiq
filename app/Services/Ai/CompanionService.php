<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiMode;
use App\Enums\AiOutcome;
use App\Enums\Journey;
use App\Services\Ai\Contracts\AiTextGenerator;
use App\Services\Ai\Data\AiReply;
use App\Services\Ai\Data\GroundingChunk;
use App\Services\Ai\Filters\InputPreFilter;
use App\Services\Ai\Filters\OutputPostFilter;
use App\Services\Ai\Filters\RedFlagInterceptor;

/**
 * Runs the AI companion pipeline (AI-Architecture §3 steps 3–10).
 *
 * Deterministic interception comes first (red flags, then the pre-filter), the model is called only
 * with approved chunks (BR-09-1), and its answer is shown only if it passes the post-filter. Every
 * other path returns an approved reply. Independent of the Clinical Rules Engine (ADR-020).
 */
final class CompanionService
{
    public function __construct(
        private readonly RedFlagInterceptor $redFlags,
        private readonly InputPreFilter $preFilter,
        private readonly GroundingRetriever $retriever,
        private readonly PromptBuilder $builder,
        private readonly AiTextGenerator $generator,
        private readonly OutputPostFilter $postFilter,
        private readonly BudgetGuard $budget,
        private readonly UsageLogger $usage,
        private readonly AiSettings $settings,
    ) {}

    /**
     * @param  list<array{role: string, text: string}>  $history
     */
    public function reply(AiMode $mode, string $locale, string $message, array $history = [], ?int $articleId = null, ?int $termId = null, ?string $preset = null, ?int $simplification = null): AiReply
    {
        if ($this->redFlags->matches($message)) {
            $this->usage->record($mode, $locale, AiOutcome::BlockedRedFlag, articleId: $articleId);

            return new AiReply(AiOutcome::BlockedRedFlag, __('ai.reply.redflag', [], $locale), action: $this->concernAction($locale));
        }

        $blocked = $this->preFilter->check($message);

        if ($blocked !== null) {
            $outcome = $blocked === InputPreFilter::PERSONAL_HEALTH ? AiOutcome::RefusedPersonalCase : AiOutcome::BlockedPrefilter;
            $this->usage->record($mode, $locale, $outcome, errorCode: $blocked, articleId: $articleId);

            return new AiReply($outcome, __("ai.reply.{$blocked}", [], $locale), action: $blocked === InputPreFilter::PII || $blocked === InputPreFilter::INJECTION ? null : $this->concernAction($locale));
        }

        if ($this->budget->exceeded()) {
            $this->usage->record($mode, $locale, AiOutcome::BudgetExceeded, articleId: $articleId);

            return new AiReply(AiOutcome::BudgetExceeded, __('ai.reply.unavailable', [], $locale));
        }

        $limits = $this->settings->limits();
        $chunks = $this->retriever->retrieve($mode, $locale, $message, $articleId, $termId, $limits['grounding_chunks']);

        if ($chunks === []) {
            $this->usage->record($mode, $locale, AiOutcome::RefusedOutOfScope, articleId: $articleId);

            return new AiReply(AiOutcome::RefusedOutOfScope, __('ai.reply.out_of_scope', [], $locale));
        }

        $prompt = $this->builder->build(
            $mode,
            $locale,
            $message,
            $preset,
            $chunks,
            $this->safeHistory($history, $limits['history_max_items']),
            $simplification ?? $this->settings->defaultSimplification(),
            $limits['max_output_tokens'],
        );

        $started = hrtime(true);

        try {
            $result = $this->generator->generate($prompt);
        } catch (AiGenerationFailed $exception) {
            $this->usage->record($mode, $locale, AiOutcome::Error, latencyMs: $this->elapsed($started), errorCode: $exception->reason, articleId: $articleId, model: $this->generator->model());

            return new AiReply(AiOutcome::Error, __('ai.reply.unavailable', [], $locale), $this->citations($chunks), model: $this->generator->model(), latencyMs: $this->elapsed($started), diagnostic: $exception->reason.($exception->status !== null ? " (HTTP {$exception->status})" : '').($exception->detail !== null ? ': '.$exception->detail : ''));
        }

        $latency = $this->elapsed($started);

        if ($result->personalCase) {
            $this->usage->record($mode, $locale, AiOutcome::RefusedPersonalCase, $result, $latency, articleId: $articleId);

            return new AiReply(AiOutcome::RefusedPersonalCase, __('ai.reply.personal_health', [], $locale), action: $this->concernAction($locale), model: $result->model, latencyMs: $latency);
        }

        if ($result->outOfScope) {
            $this->usage->record($mode, $locale, AiOutcome::RefusedOutOfScope, $result, $latency, articleId: $articleId);

            return new AiReply(AiOutcome::RefusedOutOfScope, __('ai.reply.out_of_scope', [], $locale), model: $result->model, latencyMs: $latency);
        }

        $failure = $this->postFilter->check($result, $chunks, $locale, $limits['answer_max_chars']);

        if ($failure !== null) {
            $this->usage->record($mode, $locale, AiOutcome::BlockedPostfilter, $result, $latency, $failure, $articleId);

            return new AiReply(AiOutcome::BlockedPostfilter, __('ai.reply.fallback', [], $locale), $this->citations($chunks), model: $result->model, latencyMs: $latency);
        }

        $this->usage->record($mode, $locale, AiOutcome::Answered, $result, $latency, articleId: $articleId);
        $cited = array_values(array_filter($chunks, fn (GroundingChunk $chunk): bool => in_array($chunk->id, $result->citedChunkIds, true)));

        return new AiReply(AiOutcome::Answered, trim($result->answer), $this->citations($cited), model: $result->model, latencyMs: $latency);
    }

    /**
     * Earlier turns are re-checked on every request (BR-09-6); any flagged turn is dropped.
     *
     * @param  list<array{role: string, text: string}>  $history
     * @return list<array{role: 'user'|'model', text: string}>
     */
    private function safeHistory(array $history, int $max): array
    {
        $safe = [];

        foreach (array_slice($history, -$max) as $turn) {
            $text = (string) ($turn['text'] ?? '');

            if ($text === '' || $this->redFlags->matches($text) || $this->preFilter->check($text) !== null) {
                continue;
            }

            $safe[] = ['role' => ($turn['role'] ?? '') === 'assistant' ? 'model' : 'user', 'text' => $text];
        }

        return $safe;
    }

    /**
     * Links to the articles behind the chunks, without duplicates (M09-FR-05).
     *
     * @param  list<GroundingChunk>  $chunks
     * @return list<array{title: string, url: string}>
     */
    private function citations(array $chunks): array
    {
        $citations = [];

        foreach ($chunks as $chunk) {
            $citations[$chunk->url] = ['title' => $chunk->title, 'url' => $chunk->url];
        }

        return array_values($citations);
    }

    /**
     * @return array{label: string, url: string}|null
     */
    private function concernAction(string $locale): ?array
    {
        if (! Journey::Concern->isEnabled()) {
            return null;
        }

        return ['label' => __('ai.action.check_warning_signs', [], $locale), 'url' => route('journeys.concern', ['locale' => $locale])];
    }

    private function elapsed(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }
}
