<?php

declare(strict_types=1);

namespace App\Contracts\Safety;

/**
 * Neutral contract for the warning-sign phrase list (M06-FR-09, AI-012).
 *
 * The AI companion (Module-09) reads it to stop and show emergency guidance; it never depends on the
 * Clinical Rules Engine itself, and matching here never decides a priority (ADR-020).
 */
interface SafetyLexicon
{
    /**
     * @return list<string>
     */
    public function phrases(string $locale): array;

    public function matches(string $text, string $locale): bool;
}
