<?php

declare(strict_types=1);

namespace App\Enums;

use InvalidArgumentException;

/**
 * Guidance priority levels produced by the Clinical Rules Engine (SAFE-004).
 *
 * P0 is the most urgent. Gate means the visitor is not eligible (CR-001).
 */
enum Priority: string
{
    case Gate = 'GATE';
    case P0 = 'P0';
    case P1 = 'P1';
    case P2 = 'P2';
    case P3 = 'P3';
    case P4 = 'P4';

    /**
     * Lower rank means more urgent. Gate outranks everything because it stops evaluation.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Gate => -1,
            self::P0 => 0,
            self::P1 => 1,
            self::P2 => 2,
            self::P3 => 3,
            self::P4 => 4,
        };
    }

    public function isMoreUrgentThan(self $other): bool
    {
        return $this->rank() < $other->rank();
    }

    /**
     * The highest (most urgent) priority among the given ones (SAFE-005).
     */
    public static function highest(self ...$priorities): self
    {
        if ($priorities === []) {
            throw new InvalidArgumentException('At least one priority is required.');
        }

        $highest = $priorities[0];

        foreach ($priorities as $priority) {
            if ($priority->isMoreUrgentThan($highest)) {
                $highest = $priority;
            }
        }

        return $highest;
    }
}
