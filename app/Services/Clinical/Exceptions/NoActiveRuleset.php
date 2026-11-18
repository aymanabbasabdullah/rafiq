<?php

declare(strict_types=1);

namespace App\Services\Clinical\Exceptions;

use RuntimeException;

/**
 * No ruleset may run in this environment (FR-052). Shown as HTTP 503 with the general safety message; never a default result.
 */
final class NoActiveRuleset extends RuntimeException {}
