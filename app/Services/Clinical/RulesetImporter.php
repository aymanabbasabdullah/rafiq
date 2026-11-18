<?php

declare(strict_types=1);

namespace App\Services\Clinical;

use App\Enums\Priority;
use App\Enums\ReviewStatus;
use App\Enums\RulesetStatus;
use App\Models\ClinicalQuestion;
use App\Models\ClinicalRule;
use App\Models\ClinicalRuleVersion;
use App\Models\GuidanceText;
use App\Models\RulesetRelease;
use App\Services\Audit\AuditLogger;
use App\Services\Clinical\Exceptions\InvalidRuleset;
use Illuminate\Support\Facades\DB;

/**
 * Imports a ruleset directory into the database as a Draft release (M06-FR-02).
 *
 * All-or-nothing (one transaction). A release that has entered review cannot be overwritten, and an
 * approved rule version whose definition changed is refused: the author must bump its version (BR-06-5).
 */
final class RulesetImporter
{
    public function __construct(private readonly AuditLogger $audit, private readonly ActiveRulesetResolver $resolver) {}

    public function import(string $directory, ?string $commit = null): RulesetRelease
    {
        $files = RulesetFiles::read($directory);
        $errors = (new RulesetSchemaValidator(new ForbiddenPhraseChecker))->validate($files['questions'], $files['rules'], $files['guidance']);

        if ($errors !== []) {
            throw new InvalidRuleset($errors);
        }

        $version = basename($directory);

        $release = DB::transaction(function () use ($files, $version, $commit): RulesetRelease {
            $release = RulesetRelease::query()->lockForUpdate()->firstWhere('version', $version);

            if ($release !== null && ! $release->status->acceptsImport()) {
                throw new InvalidRuleset(["{$version} is {$release->status->value}; create a new release version instead of re-importing it."]);
            }

            $release ??= new RulesetRelease(['version' => $version]);
            $release->fill([
                'status' => RulesetStatus::Draft,
                'source_hash' => $files['hash'],
                'imported_from_commit' => $commit,
                'lexicon' => $files['lexicon'],
                'test_report' => null,
                'tests_passed_at' => null,
                'submitted_by' => null,
                'submitted_at' => null,
                'review_comment' => null,
            ])->save();

            $release->ruleVersions()->detach();
            $release->questions()->delete();
            $release->guidanceTexts()->delete();

            foreach ($files['rules'] as $definition) {
                $release->ruleVersions()->attach($this->ruleVersion($definition, $commit)->id);
            }

            foreach ($files['questions'] as $index => $definition) {
                $this->question($release, $definition, $index);
            }

            foreach (array_keys($files['guidance']['en']) as $key) {
                $text = GuidanceText::query()->create([
                    'ruleset_release_id' => $release->id,
                    'key' => $key,
                    'category' => str_starts_with($key, 'topic.') ? 'doctor_topic' : 'next_step',
                ]);

                foreach (['ar', 'en'] as $locale) {
                    $text->translations()->create(['locale' => $locale, 'text_markdown' => $files['guidance'][$locale][$key], 'review_status' => ReviewStatus::Draft]);
                }
            }

            return $release;
        });

        $this->audit->record('clinical.ruleset.imported', $release, ['version' => $version, 'source_hash' => $files['hash'], 'commit' => $commit]);
        $this->resolver->forget();

        return $release;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function ruleVersion(array $definition, ?string $commit): ClinicalRuleVersion
    {
        $rule = ClinicalRule::query()->updateOrCreate(['code' => $definition['code']], ['title' => $definition['title']]);
        $hash = hash('sha256', (string) json_encode($definition));

        $existing = ClinicalRuleVersion::query()
            ->where('clinical_rule_id', $rule->id)
            ->where('version', $definition['version'])
            ->first();

        if ($existing !== null && $existing->definition_hash === $hash) {
            return $existing;
        }

        if ($existing !== null && $existing->status === ReviewStatus::Approved) {
            throw new InvalidRuleset(["{$definition['code']} v{$definition['version']} is approved and cannot change; bump its version."]);
        }

        $attributes = [
            'section' => $definition['section'],
            'definition' => $definition,
            'definition_hash' => $hash,
            'priority' => $this->highestPriority($definition)->value,
            'support' => $definition['support'],
            'status' => ReviewStatus::Draft,
            'evidence' => $definition['evidence'],
            'imported_from_commit' => $commit,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_comment' => null,
        ];

        if ($existing !== null) {
            $existing->update($attributes);

            return $existing;
        }

        return ClinicalRuleVersion::query()->create(['clinical_rule_id' => $rule->id, 'version' => $definition['version'], ...$attributes]);
    }

    /**
     * The most urgent priority a rule can produce, for listing and filtering.
     *
     * @param  array<string, mixed>  $definition
     */
    private function highestPriority(array $definition): Priority
    {
        $priorities = [];

        foreach ($definition['branches'] ?? [['outcome' => $definition['outcome']]] as $branch) {
            $priorities[] = Priority::from($branch['outcome']['priority']);

            foreach ($branch['outcome']['priority_by_journey'] ?? [] as $override) {
                $priorities[] = Priority::from($override);
            }
        }

        return Priority::highest(...$priorities);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function question(RulesetRelease $release, array $definition, int $index): void
    {
        $question = ClinicalQuestion::query()->create([
            'ruleset_release_id' => $release->id,
            'key' => $definition['key'],
            'section' => $definition['section'],
            'type' => $definition['type'],
            'definition' => $definition,
            'sort_order' => $index,
        ]);

        foreach (['ar', 'en'] as $locale) {
            $question->translations()->create(['locale' => $locale, 'prompt' => $definition['prompt'][$locale], 'review_status' => ReviewStatus::Draft]);
        }
    }
}
