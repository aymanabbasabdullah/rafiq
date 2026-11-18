<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records append-only audit log entries for admin actions (ADM-004, Database-Design §3.1).
 *
 * Summaries are scrubbed so secrets and passwords can never be persisted (Module-11 §14).
 */
class AuditLogger
{
    /**
     * @var list<string>
     */
    private const EXTRA_FORBIDDEN_KEYS = [
        'two_factor_secret',
        'two_factor_recovery_codes',
        'recovery_code',
        'code',
    ];

    /**
     * @param  array<string, mixed>  $summary
     */
    public function record(
        string $action,
        ?Model $subject = null,
        array $summary = [],
        ?User $actor = null,
        ?string $ip = null,
    ): AuditLog {
        $resolvedActor = $actor ?? (Auth::user() instanceof User ? Auth::user() : null);
        $resolvedIp = $ip ?? (app()->bound('request') ? request()->ip() : null);

        return AuditLog::query()->create([
            'actor_id' => $resolvedActor?->id,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'summary' => $this->scrub($summary),
            'ip_hash' => $this->hashIp($resolvedIp),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function scrub(array $data): array
    {
        /** @var list<string> $configured */
        $configured = config('rafiq.sensitive_keys', []);
        $forbidden = array_flip(array_map('strtolower', [...$configured, ...self::EXTRA_FORBIDDEN_KEYS]));

        $cleaned = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && isset($forbidden[strtolower($key)])) {
                $cleaned[$key] = '[REDACTED]';

                continue;
            }

            $cleaned[$key] = is_array($value) ? $this->scrub($value) : $value;
        }

        return $cleaned;
    }

    private function hashIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
