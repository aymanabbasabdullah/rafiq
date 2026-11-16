<?php

declare(strict_types=1);

namespace App\Services\Ai\Data;

/**
 * The model's structured answer (responseSchema in Gemini-Integration §3) plus usage metadata.
 */
final readonly class AiResult
{
    /**
     * @param  list<string>  $citedChunkIds
     */
    public function __construct(
        public string $answer,
        public array $citedChunkIds,
        public bool $outOfScope,
        public bool $personalCase,
        public ?string $model = null,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
    ) {}
}
