<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Clinical\Exceptions\InvalidRuleset;
use App\Services\Clinical\RulesetImporter;
use Illuminate\Console\Command;

/**
 * Imports `resources/clinical/rulesets/{version}` as a Draft release (M06-FR-02). CLI only; never over HTTP.
 */
class ClinicalImportCommand extends Command
{
    protected $signature = 'rafiq:clinical:import
        {version : Ruleset version directory, e.g. RS-2026.1}
        {--commit= : Git commit the files come from}';

    protected $description = 'Validate and import a clinical ruleset as a Draft release';

    public function handle(RulesetImporter $importer): int
    {
        $version = (string) $this->argument('version');

        if (preg_match('/^RS-\d{4}\.\d+$/', $version) !== 1) {
            $this->error('The version must look like RS-2026.1.');

            return self::FAILURE;
        }

        try {
            $release = $importer->import(resource_path("clinical/rulesets/{$version}"), $this->option('commit') ?: null);
        } catch (InvalidRuleset $exception) {
            $this->error('Import refused. Nothing was written.');

            foreach ($exception->errors as $error) {
                $this->line("  - {$error}");
            }

            return self::FAILURE;
        }

        $this->info("Imported {$release->version} as {$release->status->value} (source {$release->source_hash}).");
        $this->warn('DRAFT – PENDING QUALIFIED MEDICAL REVIEW. Draft rules only run outside production in demo mode.');

        return self::SUCCESS;
    }
}
