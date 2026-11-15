<?php

namespace Tests\Unit;

use App\Enums\Priority;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PriorityTest extends TestCase
{
    public function test_highest_returns_the_most_urgent_priority(): void
    {
        $this->assertSame(Priority::P0, Priority::highest(Priority::P3, Priority::P0, Priority::P1));
        $this->assertSame(Priority::P1, Priority::highest(Priority::P2, Priority::P1, Priority::P4));
        $this->assertSame(Priority::P4, Priority::highest(Priority::P4));
    }

    public function test_gate_outranks_every_clinical_priority(): void
    {
        $this->assertSame(Priority::Gate, Priority::highest(Priority::P0, Priority::Gate));
    }

    public function test_highest_is_independent_of_argument_order(): void
    {
        $this->assertSame(
            Priority::highest(Priority::P2, Priority::P1, Priority::P3),
            Priority::highest(Priority::P3, Priority::P2, Priority::P1),
        );
    }

    public function test_highest_requires_at_least_one_priority(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Priority::highest();
    }
}
