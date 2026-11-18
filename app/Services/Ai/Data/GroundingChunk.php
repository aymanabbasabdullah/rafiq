<?php

declare(strict_types=1);

namespace App\Services\Ai\Data;

/**
 * One piece of approved, published content given to the model as data (ADR-008).
 */
final readonly class GroundingChunk
{
    public function __construct(
        public string $id,
        public string $heading,
        public string $text,
        public string $title,
        public string $url,
        public ?int $articleId,
    ) {}
}
