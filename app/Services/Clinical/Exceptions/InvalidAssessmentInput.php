<?php

declare(strict_types=1);

namespace App\Services\Clinical\Exceptions;

use RuntimeException;

/**
 * The answers do not match the question definitions (Clinical-Safety-Framework §6). Shown as HTTP 422; no result is computed.
 */
final class InvalidAssessmentInput extends RuntimeException
{
    /**
     * @param  array<string, string>  $errors  Question key (or `_input`) ⇒ machine-readable reason.
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Invalid assessment input: '.implode(', ', array_map(
            fn (string $key, string $reason): string => "{$key}={$reason}",
            array_keys($errors),
            $errors,
        )));
    }
}
