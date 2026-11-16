<?php

declare(strict_types=1);

namespace App\Services\Ai;

use RuntimeException;

/**
 * The model call failed or returned something unusable. `code` is a short machine-readable reason
 * (timeout, rate_limited, server_error, bad_request, safety_blocked, invalid_json, not_configured).
 */
final class AiGenerationFailed extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct("AI generation failed: {$reason}");
    }
}
