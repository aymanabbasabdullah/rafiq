<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Clinical;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Clinical\ClinicalDecisionRequest;
use App\Models\ClinicalRuleVersion;
use App\Models\User;
use App\Services\Clinical\ClinicalReviewWorkflow;
use App\Services\Content\ContentWorkflowException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Rule version transitions: submit, approve, reject (M11C-FR-03).
 */
class RuleReviewController extends Controller
{
    /**
     * @var list<string>
     */
    public const ACTIONS = ['submit', 'approve', 'reject'];

    public function __invoke(ClinicalDecisionRequest $request, ClinicalReviewWorkflow $workflow, ClinicalRuleVersion $version, string $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($action === 'submit') {
            Gate::authorize('submit', $version);
        }

        try {
            match ($action) {
                'submit' => $workflow->submitRule($version, $user),
                'approve' => $workflow->approveRule($version, $user, (string) $request->validated('comment')),
                'reject' => $workflow->rejectRule($version, $user, (string) $request->validated('comment')),
            };
        } catch (ContentWorkflowException $exception) {
            if ($exception->translationKey === 'admin.content.error.four_eyes') {
                abort(403, __($exception->translationKey));
            }

            return back()->withErrors(['workflow' => __($exception->translationKey)]);
        }

        return back()->with('success', __("admin.clinical.done.{$action}"));
    }
}
