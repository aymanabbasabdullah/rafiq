<?php

declare(strict_types=1);

namespace App\Services\Clinical;

use App\Enums\ReviewStatus;
use App\Enums\RulesetStatus;
use App\Models\ClinicalRuleVersion;
use App\Models\RulesetRelease;
use App\Services\Clinical\Exceptions\NoActiveRuleset;
use Illuminate\Support\Facades\Cache;

/**
 * Chooses which ruleset may run (M06-FR-03, ADR-006):
 *
 * - Production: only a published release whose rule versions are all approved (BR-06-6).
 * - Outside production with demo mode on: the configured draft release (`rafiq.demo_ruleset`), marked demo.
 * - Otherwise: the published release only.
 *
 * Never falls back to a default: no allowed release means NoActiveRuleset (HTTP 503, BR-06-4).
 */
class ActiveRulesetResolver
{
    private ?LoadedRuleset $resolved = null;

    public function resolve(): LoadedRuleset
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $demo = ! app()->isProduction() && (bool) config('rafiq.demo_mode');

        $release = $demo
            ? RulesetRelease::query()->where('version', (string) config('rafiq.demo_ruleset'))->where('status', '!=', RulesetStatus::Retired)->first()
            : null;

        $release ??= RulesetRelease::query()->where('status', RulesetStatus::Published)->latest('published_at')->first();

        if ($release === null) {
            throw new NoActiveRuleset('No ruleset release may run in this environment.');
        }

        $isDemoRelease = $release->status !== RulesetStatus::Published;

        if (! $isDemoRelease && $release->ruleVersions()->where('status', '!=', ReviewStatus::Approved)->exists()) {
            throw new NoActiveRuleset("{$release->version} contains rule versions that are not approved.");
        }

        return $this->resolved = $this->load($release)->withDemo($isDemoRelease);
    }

    /**
     * Builds the in-memory ruleset of a release; cached by version and source hash (M06-FR-11).
     */
    public function load(RulesetRelease $release): LoadedRuleset
    {
        $data = Cache::rememberForever(
            "clinical.ruleset.{$release->version}.{$release->source_hash}",
            fn (): array => [
                'questions' => $release->questions()->get()->map(fn ($question): array => $question->definition)->all(),
                'rules' => $release->ruleVersions()->with('rule')->get()
                    ->sortBy(fn (ClinicalRuleVersion $version): int => $version->id)
                    ->map(fn (ClinicalRuleVersion $version): array => $version->definition)
                    ->values()
                    ->all(),
            ],
        );

        return new LoadedRuleset($release->version, $data['questions'], $data['rules'], $release->source_hash);
    }

    /**
     * Clears the per-request memo (after import, approval or publication).
     */
    public function forget(): void
    {
        $this->resolved = null;
    }
}
