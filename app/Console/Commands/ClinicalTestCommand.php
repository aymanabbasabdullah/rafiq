<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RulesetRelease;
use App\Services\Clinical\ActiveRulesetResolver;
use App\Services\Clinical\Exceptions\InvalidRuleset;
use App\Services\Clinical\LoadedRuleset;
use App\Services\Clinical\RulesetTestRunner;
use Illuminate\Console\Command;

/**
 * Runs the clinical test cases and prints a PASS/FAIL table (M06-FR-07). Uses the imported release
 * when it exists, otherwise the files in Git (`--files` forces the files).
 */
class ClinicalTestCommand extends Command
{
    protected $signature = 'rafiq:clinical:test
        {version : Ruleset version, e.g. RS-2026.1}
        {--files : Test the files in Git instead of the imported release}';

    protected $description = 'Run the clinical test cases against a ruleset';

    public function handle(RulesetTestRunner $runner, ActiveRulesetResolver $resolver): int
    {
        $version = (string) $this->argument('version');

        if (preg_match('/^RS-\d{4}\.\d+$/', $version) !== 1) {
            $this->error('The version must look like RS-2026.1.');

            return self::FAILURE;
        }

        $release = $this->option('files') ? null : RulesetRelease::query()->firstWhere('version', $version);

        try {
            $ruleset = $release !== null ? $resolver->load($release) : LoadedRuleset::fromDirectory(resource_path("clinical/rulesets/{$version}"));
        } catch (InvalidRuleset $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $report = $runner->run($ruleset, RulesetTestRunner::casesFor($version));

        $this->line(($release !== null ? 'Imported release' : 'Git files')." {$report['ruleset']} · source {$report['source_hash']}");
        $this->table(['Case', 'Result', 'Description', 'Details'], array_map(fn (array $result): array => [
            $result['id'],
            $result['passed'] ? 'PASS' : 'FAIL',
            $result['description'],
            implode('; ', $result['failures']),
        ], $report['results']));

        $summary = "{$report['passed']}/{$report['total']} passed.";
        $report['failed'] === 0 ? $this->info($summary) : $this->error($summary);
        $this->warn('Expected results are drafts pending qualified medical review.');

        return $report['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
