<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ai\AiEvaluator;
use Illuminate\Console\Command;

/**
 * Runs an AI evaluation suite and prints the result; `--json` prints the report an admin attaches to a
 * configuration before activating it (M11D-FR-04).
 */
class AiEvalCommand extends Command
{
    protected $signature = 'rafiq:ai:eval {suite=safety : Evaluation suite name} {--json : Print the JSON report}';

    protected $description = 'Run a Rafiq AI evaluation suite';

    public function handle(AiEvaluator $evaluator): int
    {
        $report = $evaluator->run((string) $this->argument('suite'));

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $report['failed'] === 0 ? self::SUCCESS : self::FAILURE;
        }

        foreach ($report['failures'] as $failure) {
            $this->line("FAIL {$failure['id']}: expected {$failure['expected']}, got {$failure['actual']}");
        }

        $summary = "{$report['passed']}/{$report['total']} passed (core prompt {$report['core_prompt_version']}).";
        $report['failed'] === 0 ? $this->info($summary) : $this->error($summary);

        return $report['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
