<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\ArticleTranslation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Editorial and medical review workflow for article translations (ADM-010, SAFE-003, Clinical-Review-Process §5).
 *
 *   draft ──submit──▶ pending_medical_review ──approve──▶ approved ──publish──▶ (live)
 *                         │ reject (comment) ▲                │ edit ⇒ draft (live version stays)
 *                         └──────────────────┘
 */
class ArticleWorkflow
{
    public const REVIEW_INTERVAL_MONTHS = 12;

    public function __construct(
        private readonly MarkdownRenderer $renderer,
        private readonly ChunkBuilder $chunks,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Create or update the working copy for one language. Any content change sends it back to draft;
     * the live (published) revision keeps being served until a new version is approved and published.
     *
     * @param  array{title: string, slug: string, summary: ?string, body_markdown: string}  $data
     */
    public function saveDraft(Article $article, string $locale, array $data, User $editor): ArticleTranslation
    {
        return DB::transaction(function () use ($article, $locale, $data, $editor): ArticleTranslation {
            $translation = $article->translations()->where('locale', $locale)->first();

            if ($translation === null) {
                $translation = $article->translations()->create([
                    ...$data,
                    'locale' => $locale,
                    'review_status' => ReviewStatus::Draft,
                    'publish_status' => PublishStatus::Draft,
                    'author_id' => $editor->id,
                    'last_editor_id' => $editor->id,
                ]);

                $this->audit->record('article.translation.created', $translation, ['locale' => $locale, 'article' => $article->key], $editor);

                return $translation;
            }

            $translation->fill($data);

            if (! $translation->isDirty()) {
                return $translation;
            }

            $changed = array_keys($translation->getDirty());
            $translation->forceFill([
                'review_status' => ReviewStatus::Draft,
                'review_comment' => null,
                'last_editor_id' => $editor->id,
            ])->save();

            $this->audit->record('article.translation.updated', $translation, ['locale' => $locale, 'fields' => $changed], $editor);

            return $translation;
        });
    }

    public function submit(ArticleTranslation $translation, User $editor): void
    {
        if ($translation->review_status !== ReviewStatus::Draft) {
            throw new ContentWorkflowException('admin.content.error.not_draft');
        }

        $this->assertReadyForReview($translation);

        $translation->forceFill(['review_status' => ReviewStatus::PendingMedicalReview, 'review_comment' => null])->save();

        $this->audit->record('article.translation.submitted', $translation, ['locale' => $translation->locale], $editor);
    }

    public function approve(ArticleTranslation $translation, User $reviewer, ?string $note = null): ArticleRevision
    {
        $this->assertPending($translation);
        $this->assertFourEyes($translation, $reviewer);
        $this->assertReadyForReview($translation);

        return DB::transaction(function () use ($translation, $reviewer, $note): ArticleRevision {
            $version = $translation->version + 1;

            $revision = ArticleRevision::query()->create([
                'article_translation_id' => $translation->id,
                'version' => $version,
                'title' => $translation->title,
                'slug' => $translation->slug,
                'summary' => $translation->summary,
                'body_markdown' => $translation->body_markdown,
                'body_html' => $this->renderer->toHtml($translation->body_markdown),
                'approved_by' => $reviewer->id,
                'approved_at' => now(),
                'change_note' => $note,
            ]);

            $translation->forceFill([
                'review_status' => ReviewStatus::Approved,
                'version' => $version,
                'medically_approved_by' => $reviewer->id,
                'medically_approved_at' => now(),
                'review_due_at' => now()->addMonths(self::REVIEW_INTERVAL_MONTHS)->toDateString(),
                'review_comment' => $note,
            ])->save();

            $this->audit->record('article.translation.approved', $translation, ['locale' => $translation->locale, 'version' => $version], $reviewer);

            return $revision;
        });
    }

    public function reject(ArticleTranslation $translation, User $reviewer, string $comment): void
    {
        $this->assertPending($translation);
        $this->assertFourEyes($translation, $reviewer);

        if (trim($comment) === '') {
            throw new ContentWorkflowException('admin.content.error.comment_required');
        }

        $translation->forceFill(['review_status' => ReviewStatus::Draft, 'review_comment' => $comment])->save();

        $this->audit->record('article.translation.rejected', $translation, ['locale' => $translation->locale], $reviewer);
    }

    /**
     * Make the latest approved revision live and rebuild its search chunks (M08-FR-07).
     */
    public function publish(ArticleTranslation $translation, User $editor): void
    {
        if ($translation->review_status !== ReviewStatus::Approved) {
            throw new ContentWorkflowException('admin.content.error.not_approved');
        }

        $revision = $translation->latestRevision();

        if ($revision === null) {
            throw new ContentWorkflowException('admin.content.error.not_approved');
        }

        DB::transaction(function () use ($translation, $revision, $editor): void {
            $translation->forceFill([
                'publish_status' => PublishStatus::Published,
                'published_revision_id' => $revision->id,
                'published_at' => now(),
            ])->save();

            $this->chunks->rebuild($translation, $revision);

            $this->audit->record('article.translation.published', $translation, ['locale' => $translation->locale, 'version' => $revision->version], $editor);
        });

    }

    public function hide(ArticleTranslation $translation, User $editor): void
    {
        $this->setPublishStatus($translation, PublishStatus::Hidden, 'article.translation.hidden', $editor);
    }

    public function archive(ArticleTranslation $translation, User $editor): void
    {
        $this->setPublishStatus($translation, PublishStatus::Archived, 'article.translation.archived', $editor);
    }

    private function setPublishStatus(ArticleTranslation $translation, PublishStatus $status, string $action, User $editor): void
    {
        $translation->forceFill(['publish_status' => $status])->save();

        $this->audit->record($action, $translation, ['locale' => $translation->locale], $editor);
    }

    private function assertPending(ArticleTranslation $translation): void
    {
        if ($translation->review_status !== ReviewStatus::PendingMedicalReview) {
            throw new ContentWorkflowException('admin.content.error.not_pending');
        }
    }

    /**
     * The reviewer must be a verified Medical Reviewer who neither wrote nor last edited the text (SAFE-003).
     */
    private function assertFourEyes(ArticleTranslation $translation, User $reviewer): void
    {
        if (! Gate::forUser($reviewer)->allows('four-eyes', [$translation->author_id, $translation->last_editor_id])) {
            throw new ContentWorkflowException('admin.content.error.four_eyes');
        }
    }

    /**
     * Medical content needs at least one source (BR-08-1) and non-empty text.
     */
    private function assertReadyForReview(ArticleTranslation $translation): void
    {
        $article = $translation->article()->with('sources')->firstOrFail();

        if ($article->kind->requiresSource() && $article->sources->isEmpty()) {
            throw new ContentWorkflowException('admin.content.error.source_required');
        }

        if (trim($translation->title) === '' || trim($translation->body_markdown) === '') {
            throw new ContentWorkflowException('admin.content.error.empty');
        }
    }
}
