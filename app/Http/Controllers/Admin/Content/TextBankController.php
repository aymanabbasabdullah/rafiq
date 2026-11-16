<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Enums\Locale;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\ReviewDecisionRequest;
use App\Models\Article;
use App\Models\DoctorQuestionTemplate;
use App\Models\DoctorQuestionTemplateTranslation;
use App\Models\GlossaryTermTranslation;
use App\Models\User;
use App\Services\Content\ContentWorkflowException;
use App\Services\Content\MarkdownRenderer;
use App\Services\Content\TextBankWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin management and medical review of the doctor question bank and the glossary (M11B step 5, SAFE-002).
 * Editors and Super Admins edit and submit; a verified Medical Reviewer who did not edit the text approves
 * or returns it; publishing needs an approved text.
 */
class TextBankController extends Controller
{
    public const BANKS = ['doctor-questions', 'glossary'];

    /**
     * @var list<string>
     */
    public const ACTIONS = ['submit', 'approve', 'reject', 'publish', 'hide'];

    public function index(Request $request, string $bank): Response
    {
        Gate::authorize('viewAny', Article::class);

        /** @var User $user */
        $user = $request->user();
        $locale = in_array($request->query('locale'), Locale::values(), true) ? (string) $request->query('locale') : '';
        $status = ReviewStatus::tryFrom((string) $request->query('status'))?->value ?? '';

        $query = $this->query($bank)
            ->when($locale !== '', fn (Builder $query) => $query->where('locale', $locale))
            ->when($status !== '', fn (Builder $query) => $query->where('review_status', $status))
            ->orderBy('id');

        $rows = $query->get()->map(fn (DoctorQuestionTemplateTranslation|GlossaryTermTranslation $text): array => [
            'id' => $text->id,
            'locale' => $text->locale,
            // The glossary translation has both a `term` column and a `term` relation; read the relation explicitly.
            'key' => $text instanceof GlossaryTermTranslation ? $text->getRelation('term')->key : $text->template->key,
            'group' => $text instanceof GlossaryTermTranslation ? null : $this->groupOf($text->template),
            'primary' => $text instanceof GlossaryTermTranslation ? $text->term : $text->text,
            'secondary' => $text instanceof GlossaryTermTranslation ? $text->definition_markdown : $text->help,
            'reviewStatus' => $text->review_status->value,
            'publishStatus' => $text->publish_status->value,
            'reviewComment' => $text->review_comment,
            'approvedAt' => $text->medically_approved_at?->toDateString(),
            'canApprove' => $text->review_status === ReviewStatus::PendingMedicalReview
                && Gate::forUser($user)->allows('four-eyes', [null, $text->last_editor_id]),
            'updateUrl' => route('admin.content.bank.update', ['bank' => $bank, 'text' => $text->id]),
            'actionUrl' => route('admin.content.bank.action', ['bank' => $bank, 'text' => $text->id, 'action' => '__action__']),
        ]);

        return Inertia::render('Admin/Content/TextBank', [
            'bank' => $bank,
            'rows' => $rows,
            'filters' => ['locale' => $locale, 'status' => $status],
            'filterUrl' => route('admin.content.bank.index', ['bank' => $bank]),
            'canEdit' => $user->can('viewAny', Article::class),
            'canPublish' => $user->can('publish', Article::class),
        ]);
    }

    public function update(Request $request, string $bank, int $text, TextBankWorkflow $workflow, MarkdownRenderer $renderer): RedirectResponse
    {
        Gate::authorize('viewAny', Article::class);
        $model = $this->find($bank, $text);

        $validated = $request->validate([
            'primary' => ['required', 'string', 'max:'.($bank === 'glossary' ? 120 : 300)],
            'secondary' => [$bank === 'glossary' ? 'required' : 'nullable', 'string', 'max:2000'],
        ]);

        $fields = $model instanceof GlossaryTermTranslation
            ? ['term' => $validated['primary'], 'definition_markdown' => (string) $validated['secondary'], 'definition_html' => $renderer->toHtml((string) $validated['secondary'])]
            : ['text' => $validated['primary'], 'help' => $validated['secondary'] ?? null];

        /** @var User $user */
        $user = $request->user();

        return $this->attempt(fn () => $workflow->update($model, $user, $fields), 'saved');
    }

    public function action(ReviewDecisionRequest $request, string $bank, int $text, string $action, TextBankWorkflow $workflow): RedirectResponse
    {
        $model = $this->find($bank, $text);

        /** @var User $user */
        $user = $request->user();

        match ($action) {
            'submit' => Gate::authorize('viewAny', Article::class),
            'publish', 'hide' => Gate::authorize('publish', Article::class),
            default => null, // approve/reject: four-eyes gate inside the workflow
        };

        return $this->attempt(fn () => match ($action) {
            'submit' => $workflow->submit($model, $user),
            'approve' => $workflow->approve($model, $user),
            'reject' => $workflow->reject($model, $user, (string) $request->validated('comment')),
            'publish' => $workflow->publish($model, $user),
            'hide' => $workflow->hide($model, $user),
        }, $action);
    }

    /**
     * @param  callable(): void  $transition
     */
    private function attempt(callable $transition, string $done): RedirectResponse
    {
        try {
            $transition();
        } catch (ContentWorkflowException $exception) {
            if ($exception->translationKey === 'admin.content.error.four_eyes') {
                abort(403, __($exception->translationKey));
            }

            return back()->withErrors(['workflow' => __($exception->translationKey)]);
        }

        return back()->with('success', __("admin.bank.done.{$done}"));
    }

    /**
     * @return Builder<DoctorQuestionTemplateTranslation>|Builder<GlossaryTermTranslation>
     */
    private function query(string $bank): Builder
    {
        return $bank === 'glossary'
            ? GlossaryTermTranslation::query()->with('term')
            : DoctorQuestionTemplateTranslation::query()->with('template');
    }

    private function find(string $bank, int $id): DoctorQuestionTemplateTranslation|GlossaryTermTranslation
    {
        return $this->query($bank)->findOrFail($id);
    }

    private function groupOf(DoctorQuestionTemplate $template): string
    {
        return $template->kind === DoctorQuestionTemplate::KIND_BRING
            ? __('doctor_prep.step.bring')
            : __("doctor_prep.topic.{$template->topic_key}");
    }
}
