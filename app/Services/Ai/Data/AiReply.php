<?php

declare(strict_types=1);

namespace App\Services\Ai\Data;

use App\Enums\AiOutcome;

/**
 * What the visitor sees: generated text only when `outcome` is answered, otherwise an approved reply.
 */
final readonly class AiReply
{
    /**
     * @param  list<array{title: string, url: string}>  $citations
     * @param  array{label: string, url: string}|null  $action
     */
    public function __construct(
        public AiOutcome $outcome,
        public string $text,
        public array $citations = [],
        public ?array $action = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->outcome->replyKind(),
            'outcome' => $this->outcome->value,
            'text' => $this->text,
            'citations' => $this->citations,
            'action' => $this->action,
        ];
    }
}
