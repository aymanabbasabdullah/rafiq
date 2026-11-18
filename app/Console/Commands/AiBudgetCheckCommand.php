<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ai\BudgetGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Monitoring (M12-FR-05, NFR-007): warns once per day through the application log when the AI companion
 * has used 80% or more of its daily cap. Only the percentage is logged.
 */
class AiBudgetCheckCommand extends Command
{
    public const ALERT_THRESHOLD = 0.8;

    protected $signature = 'rafiq:ai:budget-check';

    protected $description = 'Warn when today\'s AI usage reaches 80% of the daily cap';

    public function handle(BudgetGuard $budget): int
    {
        $used = $budget->usedFraction();
        $percent = (int) floor($used * 100);

        $this->line("AI daily cap used: {$percent}%");

        if ($used >= self::ALERT_THRESHOLD && config('rafiq.ai.enabled')) {
            $key = 'rafiq:ai:budget-alert:'.now()->toDateString();

            if (Cache::add($key, true, now()->endOfDay())) {
                Log::warning('Rafiq AI daily cap almost reached', ['used_percent' => $percent]);
            }
        }

        return self::SUCCESS;
    }
}
