<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiTextGenerator;
use App\Services\Ai\Data\AiPrompt;
use App\Services\Ai\Data\AiResult;

/**
 * Deterministic stand-in used outside production when no Gemini key is configured (M09-FR-09):
 * it quotes the first approved chunk it was given, so the demo shows real, cited content without
 * calling any external service. It never invents text.
 */
final class FakeAiTextGenerator implements AiTextGenerator
{
    public function model(): ?string
    {
        return 'fake';
    }

    public function generate(AiPrompt $prompt): AiResult
    {
        $chunk = $prompt->chunks[0] ?? null;

        if ($chunk === null) {
            return new AiResult('', [], outOfScope: true, personalCase: false, model: 'fake');
        }

        $sentences = preg_split('/(?<=[.!؟?])\s+/u', trim($chunk->text)) ?: [];
        $answer = trim(implode(' ', array_slice($sentences, 0, 3)));

        return new AiResult($answer, [$chunk->id], outOfScope: false, personalCase: false, model: 'fake', inputTokens: 0, outputTokens: 0);
    }
}
