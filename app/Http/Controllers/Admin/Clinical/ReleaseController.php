<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Clinical;

use App\Enums\RulesetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Clinical\ClinicalDecisionRequest;
use App\Models\ClinicalQuestion;
use App\Models\GuidanceText;
use App\Models\RulesetRelease;
use App\Models\User;
use App\Services\Clinical\ClinicalReviewWorkflow;
use App\Services\Content\ContentWorkflowException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ruleset releases (M11C-FR-04, M11C-FR-07): test report, checklist, texts, medical approval and publishing.
 */
class ReleaseController extends Controller
{
    /**
     * @var list<string>
     */
    public const ACTIONS = ['run-tests', 'submit', 'approve-texts', 'approve', 'reject'];

    public function index(Request $request, ClinicalReviewWorkflow $workflow): Response
    {
        Gate::authorize('viewAny', RulesetRelease::class);

        /** @var User $user */
        $user = $request->user();
        $releases = RulesetRelease::query()->latest('id')->get();
        $selected = $releases->firstWhere('version', $request->query('release')) ?? $releases->first();

        return Inertia::render('Admin/Clinical/Releases', [
            'releases' => $releases->map(fn (RulesetRelease $release): array => [
                'version' => $release->version,
                'status' => $release->status->value,
                'publishedAt' => $release->published_at?->toDateString(),
                'url' => route('admin.clinical.releases.index', ['release' => $release->version]),
            ]),
            'selected' => $selected !== null ? $this->details($selected, $workflow, $user) : null,
            'environment' => [
                'demoMode' => (bool) config('rafiq.demo_mode'),
                'demoRuleset' => (string) config('rafiq.demo_ruleset'),
                'assessmentEnabled' => (bool) config('rafiq.assessment_enabled'),
                'production' => app()->isProduction(),
            ],
            'rulesUrl' => route('admin.clinical.rules.index'),
        ]);
    }

    public function action(ClinicalDecisionRequest $request, ClinicalReviewWorkflow $workflow, RulesetRelease $release, string $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        match ($action) {
            'run-tests' => Gate::authorize('runTests', $release),
            'submit' => Gate::authorize('submit', $release),
            default => null, // approvals: enforced by the four-eyes gate inside the workflow
        };

        try {
            match ($action) {
                'run-tests' => $workflow->runTests($release, $user),
                'submit' => $workflow->submitRelease($release, $user),
                'approve-texts' => $workflow->approveTexts($release, $user),
                'approve' => $workflow->approveRelease($release, $user, (string) $request->validated('comment')),
                'reject' => $workflow->rejectRelease($release, $user, (string) $request->validated('comment')),
            };
        } catch (ContentWorkflowException $exception) {
            return $this->failure($exception);
        }

        return back()->with('success', __("admin.clinical.release_done.{$action}"));
    }

    /**
     * Publishing is behind password confirmation (BR-11-6) and limited to Super Admin.
     */
    public function publish(Request $request, ClinicalReviewWorkflow $workflow, RulesetRelease $release): RedirectResponse
    {
        Gate::authorize('publish', $release);

        /** @var User $user */
        $user = $request->user();

        try {
            $workflow->publish($release, $user);
        } catch (ContentWorkflowException $exception) {
            return $this->failure($exception);
        }

        return back()->with('success', __('admin.clinical.release_done.publish'));
    }

    private function failure(ContentWorkflowException $exception): RedirectResponse
    {
        if ($exception->translationKey === 'admin.content.error.four_eyes') {
            abort(403, __($exception->translationKey));
        }

        return back()->withErrors(['workflow' => __($exception->translationKey)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function details(RulesetRelease $release, ClinicalReviewWorkflow $workflow, User $user): array
    {
        $release->load(['approver', 'publisher']);
        $report = $release->test_report;
        $pending = $release->status === RulesetStatus::PendingMedicalReview;
        $canReview = $pending && Gate::allows('four-eyes', [$release->submitted_by]);

        return [
            'version' => $release->version,
            'status' => $release->status->value,
            'sourceHash' => $release->source_hash,
            'importedFromCommit' => $release->imported_from_commit,
            'submittedAt' => $release->submitted_at?->toDateTimeString(),
            'approver' => $release->approver?->name,
            'approvedAt' => $release->medically_approved_at?->toDateTimeString(),
            'publisher' => $release->publisher?->name,
            'publishedAt' => $release->published_at?->toDateTimeString(),
            'reviewComment' => $release->review_comment,
            'report' => $report !== null ? [
                'passed' => $report['passed'],
                'total' => $report['total'],
                'ranAt' => $report['ran_at'] ?? null,
                'current' => ($report['source_hash'] ?? null) === $release->source_hash,
                'results' => array_map(fn (array $result): array => [
                    'id' => $result['id'],
                    'description' => $result['description'],
                    'passed' => $result['passed'],
                    'failures' => $result['failures'],
                ], $report['results']),
            ] : null,
            'checklist' => $workflow->checklist($release),
            'texts' => $release->guidanceTexts()->with('translations')->orderBy('key')->get()->map(fn (GuidanceText $text): array => [
                'key' => $text->key,
                'ar' => $text->translations->firstWhere('locale', 'ar')?->text_markdown,
                'en' => $text->translations->firstWhere('locale', 'en')?->text_markdown,
                'approved' => $text->translations->every(fn ($translation): bool => $translation->review_status->value === 'approved'),
            ]),
            'questions' => $release->questions()->with('translations')->get()->map(fn (ClinicalQuestion $question): array => [
                'key' => $question->key,
                'section' => $question->section,
                'ar' => $question->translations->firstWhere('locale', 'ar')?->prompt,
                'en' => $question->translations->firstWhere('locale', 'en')?->prompt,
            ]),
            'can' => [
                'runTests' => $user->can('runTests', $release) && in_array($release->status, [RulesetStatus::Draft, RulesetStatus::PendingMedicalReview], true),
                'submit' => $release->status === RulesetStatus::Draft && $user->can('submit', $release),
                'review' => $canReview,
                'publish' => $release->status === RulesetStatus::Approved && $user->can('publish', $release),
            ],
            'actionUrl' => route('admin.clinical.releases.action', ['release' => $release, 'action' => '__action__']),
            'publishUrl' => route('admin.clinical.releases.publish', $release),
        ];
    }
}
