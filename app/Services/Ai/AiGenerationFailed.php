<?php

declare(strict_types=1);

namespace App\Services\Ai;

use RuntimeException;

/**
 * The model call failed or returned something unusable. `reason` is a short machine-readable code
 * (timeout, rate_limited, server_error, bad_request, safety_blocked, invalid_json, not_configured);
 * `status` is Google's HTTP status when there was one, and `detail` Google's own error message
 * (never containing credentials). Neither is ever shown to visitors.
 */
final class AiGenerationFailed extends RuntimeException
{
    public function __construct(public readonly string $reason, public readonly ?int $status = null, public readonly ?string $detail = null)
    {
        parent::__construct("AI generation failed: {$reason}");
    }
}
