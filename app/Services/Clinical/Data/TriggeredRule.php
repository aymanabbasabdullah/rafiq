<?php

declare(strict_types=1);

namespace App\Services\Clinical\Data;

use App\Enums\Priority;

/**
 * A rule that matched, with the version that produced it (CS-6 traceability).
 */
final readonly class TriggeredRule
{
    public function __construct(
        public string $code,
        public int $version,
        public Priority $priority,
    ) {}

    /**
     * @return array{code: string, version: int, priority: string}
     */
    public function toArray(): array
    {
        return ['code' => $this->code, 'version' => $this->version, 'priority' => $this->priority->value];
    }
}
