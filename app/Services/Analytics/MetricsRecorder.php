<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Increments privacy-safe aggregated daily counters (ADR-014).
 *
 * Only a metric name and a coarse dimension (page type, journey, language,
 * outcome) are recorded — never anything tied to a visitor or their answers.
 */
class MetricsRecorder
{
    private const MAX_LENGTH = 64;

    public function increment(string $metric, string $dimension = ''): void
    {
        $this->assertSafeKey($metric, 'metric');
        $this->assertSafeKey($dimension, 'dimension', allowEmpty: true);

        DB::table('daily_metrics')->upsert(
            [['date' => now()->toDateString(), 'metric' => $metric, 'dimension' => $dimension, 'value' => 1]],
            ['date', 'metric', 'dimension'],
            ['value' => DB::raw('value + 1')],
        );
    }

    /**
     * Metric names and dimensions are fixed identifiers, never free text.
     */
    private function assertSafeKey(string $value, string $name, bool $allowEmpty = false): void
    {
        if ($value === '' && $allowEmpty) {
            return;
        }

        if (mb_strlen($value) > self::MAX_LENGTH || preg_match('/^[a-z0-9_:\-]+$/', $value) !== 1) {
            throw new InvalidArgumentException("Invalid metrics {$name} [{$value}].");
        }
    }
}
