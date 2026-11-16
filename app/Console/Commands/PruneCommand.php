<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Daily retention clean-up (Data-Lifecycle §2–3, PRIV-011). Deletes in chunks to avoid long locks and
 * logs only per-table row counts — never any content.
 *
 * `ai_usage_logs` needs no aggregation step: every request already increments `daily_metrics` when it is logged.
 */
class PruneCommand extends Command
{
    protected $signature = 'rafiq:prune {--chunk=1000 : Rows deleted per batch}';

    protected $description = 'Delete operational records older than their retention period';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));

        $deleted = [
            'ai_usage_logs' => $this->pruneTable('ai_usage_logs', (int) config('rafiq.retention.ai_usage_logs_days'), $chunk),
            'audit_logs' => $this->pruneTable('audit_logs', (int) config('rafiq.retention.audit_logs_days'), $chunk),
        ];

        foreach ($deleted as $table => $count) {
            $this->line("{$table}: {$count}");
        }

        Log::info('rafiq:prune finished', ['deleted' => $deleted]);

        return self::SUCCESS;
    }

    private function pruneTable(string $table, int $days, int $chunk): int
    {
        if ($days <= 0) {
            return 0;
        }

        $cutoff = now()->subDays($days);
        $total = 0;

        do {
            $ids = DB::table($table)->where('created_at', '<', $cutoff)->orderBy('id')->limit($chunk)->pluck('id');
            $total += $ids->isEmpty() ? 0 : DB::table($table)->whereIn('id', $ids)->delete();
        } while ($ids->count() === $chunk);

        return $total;
    }
}
