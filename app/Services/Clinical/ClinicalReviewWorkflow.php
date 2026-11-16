<?php

declare(strict_types=1);

namespace App\Services\Clinical;

use App\Enums\ReviewStatus;
use App\Enums\RulesetStatus;
use App\Models\ClinicalQuestionTranslation;
use App\Models\ClinicalRuleVersion;
use App\Models\GuidanceTextTranslation;
use App\Models\RulesetRelease;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Content\ContentWorkflowException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Medical review of clinical rules and releases (Clinical-Review-Process §3–§4, Module-11-C).
 *
 * Conditions and priorities are never edited here (M11C-FR-06): they come from Git. Every medical
 * approval goes through the `four-eyes` gate, and every step is audited. Failures throw a
 * ContentWorkflowException whose message is a translation key.
 */
final class ClinicalReviewWorkflow
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ActiveRulesetResolver $resolver,
        private readonly RulesetTestRunner $runner,
    ) {}

    public function submitRule(ClinicalRuleVersion $version, User $user): void
    {
        $this->assertRuleStatus($version, ReviewStatus::Draft);
        $version->update(['status' => ReviewStatus::PendingMedicalReview, 'review_comment' => null]);
        $this->audit->record('clinical.rule.submitted', $version, ['code' => $version->rule->code, 'version' => $version->version], $user);
    }

    public function approveRule(ClinicalRuleVersion $version, User $reviewer, string $comment): void
    {
        $this->assertRuleStatus($version, ReviewStatus::PendingMedicalReview);
        $this->assertFourEyes($reviewer, $version->authored_by);

        $version->update(['status' => ReviewStatus::Approved, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'review_comment' => $comment]);
        $this->audit->record('clinical.rule.approved', $version, ['code' => $version->rule->code, 'version' => $version->version], $reviewer);
    }

    public function rejectRule(ClinicalRuleVersion $version, User $reviewer, string $comment): void
    {
        $this->assertRuleStatus($version, ReviewStatus::PendingMedicalReview);
        $this->assertFourEyes($reviewer, $version->authored_by);

        $version->update(['status' => ReviewStatus::Draft, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'review_comment' => $comment]);
        $this->audit->record('clinical.rule.rejected', $version, ['code' => $version->rule->code, 'version' => $version->version], $reviewer);
    }

    /**
     * Runs the clinical test cases against exactly what is imported and stores the report (SAFE-008).
     *
     * @return array<string, mixed>
     */
    public function runTests(RulesetRelease $release, User $user): array
    {
        $report = $this->runner->run($this->resolver->load($release), RulesetTestRunner::casesFor($release->version));
        $report['ran_at'] = now()->toIso8601String();

        $release->update(['test_report' => $report, 'tests_passed_at' => $report['failed'] === 0 ? now() : null]);
        $this->audit->record('clinical.release.tests_run', $release, ['passed' => $report['passed'], 'total' => $report['total']], $user);

        return $report;
    }

    public function submitRelease(RulesetRelease $release, User $user): void
    {
        $this->assertReleaseStatus($release, RulesetStatus::Draft);

        if (! $release->testsPassed()) {
            throw new ContentWorkflowException('admin.clinical.error.tests_required');
        }

        $release->update(['status' => RulesetStatus::PendingMedicalReview, 'submitted_by' => $user->id, 'submitted_at' => now(), 'review_comment' => null]);
        $this->audit->record('clinical.release.submitted', $release, ['version' => $release->version], $user);
    }

    /**
     * Medical approval of every question and guidance wording in the release, after reading them on the release page.
     */
    public function approveTexts(RulesetRelease $release, User $reviewer): void
    {
        $this->assertReleaseStatus($release, RulesetStatus::PendingMedicalReview);
        $this->assertFourEyes($reviewer, $release->submitted_by);

        DB::transaction(function () use ($release, $reviewer): void {
            GuidanceTextTranslation::query()
                ->whereIn('guidance_text_id', $release->guidanceTexts()->select('id'))
                ->update(['review_status' => ReviewStatus::Approved, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now()]);

            ClinicalQuestionTranslation::query()
                ->whereIn('clinical_question_id', $release->questions()->select('id'))
                ->update(['review_status' => ReviewStatus::Approved]);
        });

        $this->audit->record('clinical.release.texts_approved', $release, ['version' => $release->version], $reviewer);
    }

    public function approveRelease(RulesetRelease $release, User $reviewer, string $comment): void
    {
        $this->assertReleaseStatus($release, RulesetStatus::PendingMedicalReview);
        $this->assertFourEyes($reviewer, $release->submitted_by);
        $this->assertReadyForApproval($release);

        $release->update(['status' => RulesetStatus::Approved, 'medically_approved_by' => $reviewer->id, 'medically_approved_at' => now(), 'review_comment' => $comment]);
        $this->audit->record('clinical.release.approved', $release, ['version' => $release->version], $reviewer);
    }

    public function rejectRelease(RulesetRelease $release, User $reviewer, string $comment): void
    {
        $this->assertReleaseStatus($release, RulesetStatus::PendingMedicalReview);
        $this->assertFourEyes($reviewer, $release->submitted_by);

        $release->update(['status' => RulesetStatus::Draft, 'review_comment' => $comment]);
        $this->audit->record('clinical.release.rejected', $release, ['version' => $release->version], $reviewer);
    }

    /**
     * Publishing makes the release the one production runs; the previous one becomes superseded.
     */
    public function publish(RulesetRelease $release, User $user): void
    {
        $this->assertReleaseStatus($release, RulesetStatus::Approved);
        $this->assertReadyForApproval($release);

        DB::transaction(function () use ($release, $user): void {
            RulesetRelease::query()
                ->where('status', RulesetStatus::Published)
                ->whereKeyNot($release->id)
                ->update(['status' => RulesetStatus::Superseded]);

            $release->update(['status' => RulesetStatus::Published, 'published_by' => $user->id, 'published_at' => now()]);
        });

        $this->resolver->forget();
        $this->audit->record('clinical.release.published', $release, ['version' => $release->version, 'source_hash' => $release->source_hash], $user);
    }

    /**
     * Release checklist (Clinical-Review-Process §4 step 3).
     *
     * @return array{testsPassed: bool, rulesApproved: int, rulesTotal: int, textsApproved: bool}
     */
    public function checklist(RulesetRelease $release): array
    {
        $rulesTotal = $release->ruleVersions()->count();

        return [
            'testsPassed' => $release->testsPassed(),
            'rulesApproved' => $release->ruleVersions()->where('status', ReviewStatus::Approved)->count(),
            'rulesTotal' => $rulesTotal,
            'textsApproved' => ! GuidanceTextTranslation::query()->whereIn('guidance_text_id', $release->guidanceTexts()->select('id'))->where('review_status', '!=', ReviewStatus::Approved)->exists()
                && ! ClinicalQuestionTranslation::query()->whereIn('clinical_question_id', $release->questions()->select('id'))->where('review_status', '!=', ReviewStatus::Approved)->exists(),
        ];
    }

    private function assertReadyForApproval(RulesetRelease $release): void
    {
        $checklist = $this->checklist($release);

        if (! $checklist['testsPassed']) {
            throw new ContentWorkflowException('admin.clinical.error.tests_required');
        }

        if ($checklist['rulesApproved'] !== $checklist['rulesTotal']) {
            throw new ContentWorkflowException('admin.clinical.error.rules_not_approved');
        }

        if (! $checklist['textsApproved']) {
            throw new ContentWorkflowException('admin.clinical.error.texts_not_approved');
        }
    }

    private function assertFourEyes(User $reviewer, ?int $authorId): void
    {
        if (Gate::forUser($reviewer)->denies('four-eyes', [$authorId])) {
            throw new ContentWorkflowException('admin.content.error.four_eyes');
        }
    }

    private function assertRuleStatus(ClinicalRuleVersion $version, ReviewStatus $expected): void
    {
        if ($version->status !== $expected) {
            throw new ContentWorkflowException('admin.clinical.error.wrong_status');
        }
    }

    private function assertReleaseStatus(RulesetRelease $release, RulesetStatus $expected): void
    {
        if ($release->status !== $expected) {
            throw new ContentWorkflowException('admin.clinical.error.wrong_status');
        }
    }
}
