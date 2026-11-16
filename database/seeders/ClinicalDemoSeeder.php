<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RulesetStatus;
use App\Models\RulesetRelease;
use App\Services\Clinical\RulesetImporter;
use Illuminate\Database\Seeder;

/**
 * Imports the draft demo ruleset (RS-2026.1) so the clinical journeys can be demonstrated.
 *
 * Runs only in local/testing environments or when demo mode is enabled. It never approves or publishes
 * anything: draft rules still only run outside production with the demo banner (ADR-006).
 */
class ClinicalDemoSeeder extends Seeder
{
    public function run(RulesetImporter $importer): void
    {
        if (! app()->environment(['local', 'testing']) && ! config('rafiq.demo_mode')) {
            return;
        }

        $version = (string) config('rafiq.demo_ruleset');
        $existing = RulesetRelease::query()->firstWhere('version', $version);

        if ($existing !== null && $existing->status !== RulesetStatus::Draft) {
            return;
        }

        $importer->import(resource_path("clinical/rulesets/{$version}"));
    }
}
