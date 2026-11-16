<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Enums\RulesetStatus;
use App\Models\ClinicalRuleVersion;
use App\Models\RulesetRelease;
use App\Services\Clinical\ActiveRulesetResolver;
use App\Services\Clinical\ClinicalRulesEngine;
use App\Services\Clinical\Data\AssessmentInput;
use App\Services\Clinical\Exceptions\InvalidRuleset;
use App\Services\Clinical\Exceptions\NoActiveRuleset;
use App\Services\Clinical\ForbiddenPhraseChecker;
use App\Services\Clinical\LoadedRuleset;
use App\Services\Clinical\RulesetImporter;
use App\Services\Clinical\RulesetTestRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Module-06: the deterministic engine against the draft catalogue's test cases, the import pipeline and
 * the environment rules. Expected results are drafts pending qualified medical review.
 */
class ClinicalRulesEngineTest extends TestCase
{
    use RefreshDatabase;

    private const VERSION = 'RS-2026.1';

    private function ruleset(): LoadedRuleset
    {
        return LoadedRuleset::fromDirectory(resource_path('clinical/rulesets/'.self::VERSION), demo: true);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function clinicalCases(): array
    {
        $file = json_decode((string) file_get_contents(__DIR__.'/../../resources/clinical/test-cases/'.self::VERSION.'.json'), true);
        $cases = [];

        foreach ($file['cases'] as $case) {
            $cases["{$case['id']} {$case['description']}"] = [$case];
        }

        return $cases;
    }

    /**
     * TC-01..TC-28, TC-33..TC-37 and TC-38..TC-49 (routing, risk domains and escalation overrides) (Clinical-Test-Cases.md).
     *
     * @param  array<string, mixed>  $case
     */
    #[DataProvider('clinicalCases')]
    public function test_clinical_case(array $case): void
    {
        $result = app(RulesetTestRunner::class)->runCase($this->ruleset(), $case);

        $this->assertTrue($result['passed'], implode("\n", $result['failures']));
    }

    public function test_tc29_the_result_does_not_depend_on_the_interface_language(): void
    {
        $engine = new ClinicalRulesEngine;
        $ruleset = $this->ruleset();
        $input = new AssessmentInput('concern', true, RulesetTestRunner::withConventions($ruleset, [
            'journey' => 'concern',
            'answers' => ['ss_current_pain_or_swelling' => 'yes', 'ss_fever_with_pain_or_swelling' => 'yes', 'ss_new_lump_or_change' => 'yes'],
        ]));

        App::setLocale('ar');
        $arabic = $engine->evaluate($ruleset, $input)->toArray();
        App::setLocale('en');
        $english = $engine->evaluate($ruleset, $input)->toArray();

        $this->assertSame($arabic, $english);
        $this->assertSame('P1', $arabic['priority']);
    }

    public function test_guidance_order_follows_priority_then_rule_code_without_duplicates(): void
    {
        $ruleset = $this->ruleset();
        $result = (new ClinicalRulesEngine)->evaluate($ruleset, new AssessmentInput('concern', true, RulesetTestRunner::withConventions($ruleset, [
            'journey' => 'concern',
            'answers' => ['ss_current_pain_or_swelling' => 'yes', 'ss_sudden_severe_pain' => 'yes', 'ss_pain_over_1h_or_at_rest' => 'yes'],
        ])));

        $this->assertSame(['CR-002', 'CR-017'], $result->triggeredCodes());
        $this->assertSame(['guidance.p0.emergency', 'guidance.p0.dont_drive', 'guidance.p0.bring_meds'], $result->guidanceKeys);
        $this->assertTrue($result->demo);
        $this->assertSame(self::VERSION, $result->rulesetVersion);
    }

    public function test_tc30_production_without_a_published_release_has_no_ruleset(): void
    {
        app(RulesetImporter::class)->import(resource_path('clinical/rulesets/'.self::VERSION));
        $this->app['env'] = 'production';
        config(['rafiq.demo_mode' => false]);

        $this->expectException(NoActiveRuleset::class);

        app(ActiveRulesetResolver::class)->resolve();
    }

    public function test_tc31_demo_mode_outside_production_runs_the_draft_release_marked_demo(): void
    {
        app(RulesetImporter::class)->import(resource_path('clinical/rulesets/'.self::VERSION));
        config(['rafiq.demo_mode' => true]);

        $ruleset = app(ActiveRulesetResolver::class)->resolve();
        $result = (new ClinicalRulesEngine)->evaluate($ruleset, new AssessmentInput('concern', true, RulesetTestRunner::withConventions($ruleset, [
            'journey' => 'concern',
            'answers' => ['ss_current_pain_or_swelling' => 'yes', 'ss_sudden_severe_pain' => 'yes'],
        ])));

        $this->assertSame('P0', $result->priority?->value);
        $this->assertTrue($result->demo);
    }

    public function test_draft_rules_do_not_run_without_demo_mode_even_outside_production(): void
    {
        app(RulesetImporter::class)->import(resource_path('clinical/rulesets/'.self::VERSION));
        config(['rafiq.demo_mode' => false]);

        $this->expectException(NoActiveRuleset::class);

        app(ActiveRulesetResolver::class)->resolve();
    }

    public function test_a_published_release_with_an_unapproved_rule_version_is_refused(): void
    {
        $release = app(RulesetImporter::class)->import(resource_path('clinical/rulesets/'.self::VERSION));
        $release->update(['status' => RulesetStatus::Published, 'published_at' => now()]);
        config(['rafiq.demo_mode' => false]);

        $this->expectException(NoActiveRuleset::class);

        app(ActiveRulesetResolver::class)->resolve();
    }

    public function test_import_stores_the_release_as_draft_with_the_git_hash_and_runs_identically(): void
    {
        $release = app(RulesetImporter::class)->import(resource_path('clinical/rulesets/'.self::VERSION), 'abc1234');
        $files = $this->ruleset();

        $this->assertSame(RulesetStatus::Draft, $release->status);
        $this->assertSame($files->sourceHash, $release->source_hash);
        $this->assertSame(20, $release->ruleVersions()->count());
        $this->assertSame(19, $release->questions()->count());
        $this->assertFalse(ClinicalRuleVersion::query()->where('status', '!=', ReviewStatus::Draft)->exists());
        $this->assertDatabaseHas('audit_logs', ['action' => 'clinical.ruleset.imported']);

        $report = app(RulesetTestRunner::class)->run(app(ActiveRulesetResolver::class)->load($release), RulesetTestRunner::casesFor(self::VERSION));
        $this->assertSame(0, $report['failed']);

        // Importing the same files again reuses the same rule versions.
        app(RulesetImporter::class)->import(resource_path('clinical/rulesets/'.self::VERSION));
        $this->assertSame(20, ClinicalRuleVersion::query()->count());
    }

    public function test_import_refuses_a_release_that_has_entered_review(): void
    {
        $release = app(RulesetImporter::class)->import(resource_path('clinical/rulesets/'.self::VERSION));
        $release->update(['status' => RulesetStatus::PendingMedicalReview]);

        $this->expectException(InvalidRuleset::class);

        app(RulesetImporter::class)->import(resource_path('clinical/rulesets/'.self::VERSION));
    }

    public function test_an_approved_rule_version_cannot_change_without_a_new_version(): void
    {
        app(RulesetImporter::class)->import(resource_path('clinical/rulesets/'.self::VERSION));
        ClinicalRuleVersion::query()->whereHas('rule', fn ($query) => $query->where('code', 'CR-002'))->update(['status' => ReviewStatus::Approved]);

        $directory = $this->copyRuleset('RS-2099.1', function (array $rules): array {
            $rules['rules'][1]['outcome']['flags'] = ['changed'];

            return $rules;
        });

        try {
            app(RulesetImporter::class)->import($directory);
            $this->fail('An approved rule version was changed.');
        } catch (InvalidRuleset $exception) {
            $this->assertStringContainsString('CR-002 v1 is approved', $exception->getMessage());
        } finally {
            File::deleteDirectory($directory);
        }

        $this->assertDatabaseMissing('ruleset_releases', ['version' => 'RS-2099.1']);
    }

    public function test_invalid_rule_files_are_refused_and_nothing_is_written(): void
    {
        $directory = $this->copyRuleset('RS-2099.2', function (array $rules): array {
            $rules['rules'][1]['when'] = ['q' => 'ss_sudden_severe_pain', 'op' => 'regex', 'value' => '.*'];
            $rules['rules'][2]['outcome']['priority'] = 'P9';
            $rules['rules'][5]['outcome']['stop'] = true;

            return $rules;
        });

        try {
            app(RulesetImporter::class)->import($directory);
            $this->fail('Invalid rules were imported.');
        } catch (InvalidRuleset $exception) {
            $this->assertStringContainsString('known op', $exception->getMessage());
            $this->assertStringContainsString('unknown priority', $exception->getMessage());
            $this->assertStringContainsString('only P0 or GATE may stop', $exception->getMessage());
        } finally {
            File::deleteDirectory($directory);
        }

        $this->assertSame(0, RulesetRelease::query()->count());
    }

    public function test_forbidden_phrases_are_detected_in_both_languages(): void
    {
        $checker = new ForbiddenPhraseChecker;

        $this->assertNotEmpty($checker->find('Good news: nothing to worry about.', 'en'));
        $this->assertNotEmpty($checker->find('لا داعيَ للقلق، حالتك طبيعية.', 'ar'));
        $this->assertNotEmpty($checker->find('Your chance is 80 % this year.', 'en'));
        $this->assertSame([], $checker->find('Talk to a doctor about fertility preservation.', 'en'));
    }

    public function test_the_clinical_engine_never_depends_on_the_ai_layer(): void
    {
        foreach (File::allFiles(app_path('Services/Clinical')) as $file) {
            $this->assertStringNotContainsString('App\\Services\\Ai', $file->getContents(), $file->getFilename());
            $this->assertStringNotContainsString('Gemini', $file->getContents(), $file->getFilename());
        }
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $mutate
     */
    private function copyRuleset(string $version, callable $mutate): string
    {
        $directory = storage_path("framework/testing/clinical/{$version}");
        File::deleteDirectory($directory);
        File::copyDirectory(resource_path('clinical/rulesets/'.self::VERSION), $directory);

        $rules = json_decode((string) file_get_contents("{$directory}/rules.json"), true);
        file_put_contents("{$directory}/rules.json", json_encode($mutate($rules), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $directory;
    }
}
