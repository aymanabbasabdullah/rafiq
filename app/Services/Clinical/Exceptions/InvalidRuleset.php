<?php

declare(strict_types=1);

namespace App\Services\Clinical\Exceptions;

use RuntimeException;

/**
 * Ruleset files failed schema or safety checks (M06-FR-01, M06-FR-08). Nothing is written.
 */
final class InvalidRuleset extends RuntimeException
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct("Invalid ruleset:\n- ".implode("\n- ", $errors));
    }
}
