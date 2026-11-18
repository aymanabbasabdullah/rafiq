<?php

declare(strict_types=1);

namespace App\Services\Ai\Data;

/**
 * Everything sent to the model for one request. Contains no identifier of the visitor (GR-06).
 */
final readonly class AiPrompt
{
    /**
     * @param  list<array{role: 'user'|'model', text: string}>  $history
     * @param  list<GroundingChunk>  $chunks
     */
    public function __construct(
        public string $systemInstruction,
        public array $history,
        public string $userText,
        public array $chunks,
        public int $maxOutputTokens,
    ) {}

    /**
     * @return list<string>
     */
    public function chunkIds(): array
    {
        return array_map(fn (GroundingChunk $chunk): string => $chunk->id, $this->chunks);
    }
}
