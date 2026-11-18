<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\LogRecord;

/**
 * Removes the values of sensitive keys from log context before anything is written (SEC-013, PRIV-009).
 *
 * Health answers, AI messages and credentials must never reach log files,
 * even when they appear inside exception context.
 */
class ScrubSensitiveData
{
    public const REDACTED = '[redacted]';

    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(fn (LogRecord $record): LogRecord => $this->scrubRecord($record));
    }

    public function scrubRecord(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->scrub($record->context),
            extra: $this->scrub($record->extra),
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public function scrub(array $data): array
    {
        $sensitiveKeys = array_map('mb_strtolower', (array) config('rafiq.sensitive_keys', []));

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(mb_strtolower($key), $sensitiveKeys, true)) {
                $data[$key] = self::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->scrub($value);
            }
        }

        return $data;
    }
}
