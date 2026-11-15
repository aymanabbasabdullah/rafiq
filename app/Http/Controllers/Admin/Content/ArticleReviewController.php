<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\ReviewDecisionRequest;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\User;
use App\Services\Content\ArticleWorkflow;
use App\Services\Content\ContentWorkflowException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Workflow transitions for one translation: submit, approve, reject, publish, hide, archive (M11B-FR-03/04).
 */
class ArticleReviewController extends Controller
{
    /**
     * @var list<string>
     */
    public const ACTIONS = ['submit', 'approve', 'reject', 'publish', 'hide', 'archive'];

    public function __invoke(ReviewDecisionRequest $request, ArticleWorkflow $workflow, ArticleTranslation $translation, string $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $article = $translation->article;

        match ($action) {
            'submit' => Gate::authorize('update', $article),
            'publish', 'hide', 'archive' => Gate::authorize('publish', Article::class),
            default => null, // approve/reject: enforced by the four-eyes gate inside the workflow
        };

        try {
            match ($action) {
                'submit' => $workflow->submit($translation, $user),
                'approve' => $workflow->approve($translation, $user, $request->validated('comment')),
                'reject' => $workflow->reject($translation, $user, (string) $request->validated('comment')),
                'publish' => $workflow->publish($translation, $user),
                'hide' => $workflow->hide($translation, $user),
                'archive' => $workflow->archive($translation, $user),
            };
        } catch (ContentWorkflowException $exception) {
            if ($exception->translationKey === 'admin.content.error.four_eyes') {
                abort(403, __($exception->translationKey));
            }

            return back()->withErrors(['workflow' => __($exception->translationKey)]);
        }

        return back()->with('success', __("admin.content.done.{$action}"));
    }
}
