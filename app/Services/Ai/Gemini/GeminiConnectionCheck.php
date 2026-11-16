<?php

declare(strict_types=1);

namespace App\Services\Ai\Gemini;

use App\Services\Ai\AiGenerationFailed;
use App\Services\Ai\Contracts\AiTextGenerator;
use App\Services\Ai\Data\AiPrompt;

/**
 * Live connectivity check through the assistant's own generator: the same provider binding, the same
 * `generateContent` endpoint, headers, JSON response schema and error handling as real answers.
 *
 * Sends only a fixed technical prompt with no approved content and no visitor data. Refuses to run on
 * the fake provider, so a fake answer can never be mistaken for a live one. Never returns the API key.
 */
final class GeminiConnectionCheck
{
    public const PROMPT = 'Reply with exactly: Rafiq Gemini connection successful.';

    public const EXPECTED = 'Rafiq Gemini connection successful.';

    private const SYSTEM = 'This is a technical connectivity check. Put your reply in the "answer" field, use an empty "cited_chunk_ids" list, and set "out_of_scope" and "personal_case" to false.';

    public function __construct(private readonly AiTextGenerator $generator) {}

    /**
     * @return array{ok: bool, provider: string, model: string|null, text: string|null, matches: bool, status: int|null, error: string|null, latency_ms: int|null}
     */
    public function run(): array
    {
        $result = ['ok' => false, 'provider' => $this->provider(), 'model' => $this->generator->model(), 'text' => null, 'matches' => false, 'status' => null, 'error' => null, 'latency_ms' => null];

        if (! $this->generator instanceof GeminiTextGenerator) {
            return [...$result, 'error' => 'The configured provider is not Gemini (RAFIQ_AI_PROVIDER); a live check needs the real provider.'];
        }

        $started = hrtime(true);

        try {
            $answer = $this->generator->generate(new AiPrompt(self::SYSTEM, [], self::PROMPT, [], 64));
        } catch (AiGenerationFailed $exception) {
            return [
                ...$result,
                'status' => $exception->status,
                'latency_ms' => $this->elapsed($started),
                'error' => $exception->reason.($exception->detail !== null ? ": {$exception->detail}" : ''),
            ];
        }

        $text = trim($answer->answer);

        return [
            ...$result,
            'ok' => $text !== '',
            'model' => $answer->model,
            'text' => $text,
            'matches' => $text === self::EXPECTED,
            'status' => 200,
            'latency_ms' => $this->elapsed($started),
            'error' => $text === '' ? 'Google returned an empty answer.' : null,
        ];
    }

    private function provider(): string
    {
        return $this->generator instanceof GeminiTextGenerator ? 'gemini' : 'fake';
    }

    private function elapsed(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }
}
