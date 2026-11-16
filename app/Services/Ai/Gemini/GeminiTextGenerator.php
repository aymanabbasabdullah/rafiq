<?php

declare(strict_types=1);

namespace App\Services\Ai\Gemini;

use App\Services\Ai\AiGenerationFailed;
use App\Services\Ai\Contracts\AiTextGenerator;
use App\Services\Ai\Data\AiPrompt;
use App\Services\Ai\Data\AiResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;

/**
 * Calls Gemini `generateContent` with a JSON response schema (Gemini-Integration §3–§4).
 *
 * Never sends tools (no Search/Maps grounding), cached content, files, labels or any visitor
 * identifier, and never logs prompt or answer text. One retry on 429/5xx; a 429 after the retry
 * pauses all AI calls for 60 seconds.
 */
final class GeminiTextGenerator implements AiTextGenerator
{
    public const BACKOFF_KEY = 'rafiq.ai.backoff';

    public function __construct(
        private readonly string $apiKey,
        private readonly ?string $model,
        private readonly string $baseUrl,
        private readonly int $timeout,
    ) {}

    public function model(): ?string
    {
        return $this->model;
    }

    public function generate(AiPrompt $prompt): AiResult
    {
        if ($this->model === null || $this->model === '' || $this->apiKey === '') {
            throw new AiGenerationFailed('not_configured');
        }

        if (Cache::has(self::BACKOFF_KEY)) {
            throw new AiGenerationFailed('rate_limited');
        }

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withHeaders(['x-goog-api-key' => $this->apiKey])
                ->acceptJson()
                ->timeout($this->timeout)
                ->retry(2, 1500, fn (\Throwable $exception): bool => $exception instanceof RequestException
                    && ($exception->response->status() === 429 || $exception->response->serverError()), throw: false)
                ->post("/v1beta/models/{$this->model}:generateContent", $this->payload($prompt));
        } catch (ConnectionException) {
            throw new AiGenerationFailed('timeout');
        }

        return $this->parse($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(AiPrompt $prompt): array
    {
        $contents = [];

        foreach ($prompt->history as $turn) {
            $contents[] = ['role' => $turn['role'], 'parts' => [['text' => $turn['text']]]];
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $prompt->userText]]];

        return [
            'systemInstruction' => ['parts' => [['text' => $prompt->systemInstruction]]],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => $prompt->maxOutputTokens,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'answer' => ['type' => 'STRING'],
                        'cited_chunk_ids' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                        'out_of_scope' => ['type' => 'BOOLEAN'],
                        'personal_case' => ['type' => 'BOOLEAN'],
                    ],
                    'required' => ['answer', 'cited_chunk_ids', 'out_of_scope', 'personal_case'],
                ],
            ],
        ];
    }

    private function parse(Response $response): AiResult
    {
        if ($response->status() === 429) {
            Cache::put(self::BACKOFF_KEY, true, 60);

            throw new AiGenerationFailed('rate_limited');
        }

        if ($response->serverError()) {
            throw new AiGenerationFailed('server_error');
        }

        if ($response->failed()) {
            throw new AiGenerationFailed('bad_request');
        }

        $data = $response->json();

        if (isset($data['promptFeedback']['blockReason']) || ($data['candidates'][0]['finishReason'] ?? null) === 'SAFETY') {
            throw new AiGenerationFailed('safety_blocked');
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        try {
            $answer = is_string($text) ? json_decode($text, true, 8, JSON_THROW_ON_ERROR) : null;
        } catch (JsonException) {
            $answer = null;
        }

        if (! is_array($answer) || ! is_string($answer['answer'] ?? null) || ! is_array($answer['cited_chunk_ids'] ?? null)) {
            throw new AiGenerationFailed('invalid_json');
        }

        return new AiResult(
            answer: $answer['answer'],
            citedChunkIds: array_values(array_filter($answer['cited_chunk_ids'], 'is_string')),
            outOfScope: (bool) ($answer['out_of_scope'] ?? false),
            personalCase: (bool) ($answer['personal_case'] ?? false),
            model: $this->model,
            inputTokens: isset($data['usageMetadata']['promptTokenCount']) ? (int) $data['usageMetadata']['promptTokenCount'] : null,
            outputTokens: isset($data['usageMetadata']['candidatesTokenCount']) ? (int) $data['usageMetadata']['candidatesTokenCount'] : null,
        );
    }
}
