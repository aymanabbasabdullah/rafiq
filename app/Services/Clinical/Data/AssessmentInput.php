<?php

declare(strict_types=1);

namespace App\Services\Clinical\Data;

/**
 * What the engine evaluates: the journey, the age-gate flag from the session (M03) and the answers.
 * Never logged or stored (PRIV-002).
 */
final readonly class AssessmentInput
{
    /**
     * @param  array<string, mixed>  $answers  Question key ⇒ answer (string, list of strings, or null when skipped).
     */
    public function __construct(
        public string $journey,
        public bool $ageConfirmed,
        public array $answers,
    ) {}
}
