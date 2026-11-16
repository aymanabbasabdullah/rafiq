<?php

declare(strict_types=1);

namespace App\Services\Ai\Filters;

use App\Contracts\Safety\SafetyLexicon;

/**
 * Stops any message that mentions a warning sign before anything else happens (GR-02, BR-09-2).
 * Checks both languages, because visitors often mix them. It never decides a priority: the visitor
 * gets the approved emergency message and a link to the warning-signs check.
 */
final class RedFlagInterceptor
{
    public function __construct(private readonly SafetyLexicon $lexicon) {}

    public function matches(string $text): bool
    {
        return $this->lexicon->matches($text, 'ar') || $this->lexicon->matches($text, 'en');
    }
}
