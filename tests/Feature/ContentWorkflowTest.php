<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\ArticleTranslation;
use App\Models\ContentCategory;
use App\Models\Source;
use App\Models\User;
use App\Services\Content\ArticleWorkflow;
use App\Services\Content\ContentWorkflowException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use LogicException;
use Tests\TestCase;

/**
 * Module-11-B: content management, medical review workflow, four-eyes rule and permissions.
 */
class ContentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private User $reviewer;

    private ContentCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = User::factory()->contentEditor()->create();
        $this->reviewer = User::factory()->medicalReviewer()->create();
        $this->category = ContentCategory::factory()->translated()->create();
    }

    private function draft(bool $withSource = true, ?User $author = null): ArticleTranslation
    {
        $article = Article::factory()->create(['content_category_id' => $this->category->id]);

        if ($withSource) {
            $article->sources()->attach(Source::factory()->create()->id, ['support_level' => 'direct']);
        }

        return app(ArticleWorkflow::class)->saveDraft($article, 'en', [
            'title' => 'Draft', 'slug' => 'draft-'.$article->id, 'summary' => null, 'body_markdown' => 'Body.',
        ], $author ?? $this->editor);
    }

    private function actionUrl(ArticleTranslation $translation, string $action): string
    {
        return route('admin.content.translations.action', ['translation' => $translation->id, 'action' => $action]);
    }

    public function test_medical_content_needs_a_source_before_review(): void
    {
        $translation = $this->draft(withSource: false);

        $this->actingAs($this->editor)
            ->post($this->actionUrl($translation, 'submit'))
            ->assertSessionHasErrors('workflow');

        $this->assertSame(ReviewStatus::Draft, $translation->refresh()->review_status);
    }

    public function test_full_workflow_submit_approve_publish_creates_an_immutable_revision_and_audit_trail(): void
    {
        $translation = $this->draft();

        $this->actingAs($this->editor)->post($this->actionUrl($translation, 'submit'))->assertSessionHasNoErrors();
        $this->actingAs($this->reviewer)->post($this->actionUrl($translation, 'approve'), ['comment' => 'Looks right'])->assertSessionHasNoErrors();

        $translation->refresh();
        $this->assertSame(ReviewStatus::Approved, $translation->review_status);
        $this->assertSame(1, $translation->version);
        $this->assertSame($this->reviewer->id, $translation->medically_approved_by);
        $this->assertSame(now()->addMonths(12)->toDateString(), $translation->review_due_at?->toDateString());

        $this->actingAs($this->editor)->post($this->actionUrl($translation, 'publish'))->assertSessionHasNoErrors();
        $this->assertTrue($translation->refresh()->isLive());

        foreach (['submitted', 'approved', 'published'] as $step) {
            $this->assertDatabaseHas('audit_logs', ['action' => "article.translation.{$step}", 'subject_id' => $translation->id]);
        }

        $this->expectException(LogicException::class);
        ArticleRevision::query()->firstOrFail()->update(['title' => 'Tampered']);
    }

    public function test_reviewers_cannot_approve_text_they_wrote_or_last_edited(): void
    {
        $translation = $this->draft(author: $this->reviewer);
        app(ArticleWorkflow::class)->submit($translation, $this->reviewer);

        $this->actingAs($this->reviewer)->post($this->actionUrl($translation, 'approve'))->assertForbidden();
        $this->assertSame(ReviewStatus::PendingMedicalReview, $translation->refresh()->review_status);
    }

    public function test_editors_and_unverified_reviewers_cannot_give_medical_approval(): void
    {
        $translation = $this->draft();
        app(ArticleWorkflow::class)->submit($translation, $this->editor);
        $unverified = User::factory()->medicalReviewer(verified: false)->create();
        $otherEditor = User::factory()->contentEditor()->create();

        $this->actingAs($otherEditor)->post($this->actionUrl($translation, 'approve'))->assertForbidden();
        $this->actingAs($unverified)->post($this->actionUrl($translation, 'approve'))->assertForbidden();
    }

    public function test_rejection_requires_a_comment_and_returns_the_text_to_draft(): void
    {
        $translation = $this->draft();
        app(ArticleWorkflow::class)->submit($translation, $this->editor);

        $this->actingAs($this->reviewer)->post($this->actionUrl($translation, 'reject'))->assertSessionHasErrors('comment');

        $this->actingAs($this->reviewer)->post($this->actionUrl($translation, 'reject'), ['comment' => 'Cite the source for the second claim.']);

        $translation->refresh();
        $this->assertSame(ReviewStatus::Draft, $translation->review_status);
        $this->assertSame('Cite the source for the second claim.', $translation->review_comment);
    }

    public function test_nothing_can_be_published_before_medical_approval(): void
    {
        $translation = $this->draft();

        $this->expectException(ContentWorkflowException::class);
        app(ArticleWorkflow::class)->publish($translation, $this->editor);
    }

    public function test_reviewers_cannot_publish(): void
    {
        $translation = $this->draft();
        $workflow = app(ArticleWorkflow::class);
        $workflow->submit($translation, $this->editor);
        $workflow->approve($translation->refresh(), $this->reviewer);

        $this->actingAs($this->reviewer)->post($this->actionUrl($translation, 'publish'))->assertForbidden();
        $this->assertSame(PublishStatus::Draft, $translation->refresh()->publish_status);
    }

    public function test_admins_without_a_content_role_cannot_open_content_management(): void
    {
        $roleless = User::factory()->create();

        $this->actingAs($roleless)->get('/admin/content')->assertForbidden();
        $this->actingAs($this->editor)->get('/admin/content')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Content/Index'));
    }

    public function test_creating_an_article_with_both_languages_and_an_arabic_slug(): void
    {
        $source = Source::factory()->create();

        $this->actingAs($this->editor)->post('/admin/content/articles', [
            'key' => 'new-article',
            'content_category_id' => $this->category->id,
            'kind' => 'article',
            'ai_enabled' => true,
            'sort_order' => 1,
            'sources' => [['id' => $source->id, 'support_level' => 'direct', 'claim_note' => null]],
            'translations' => [
                'ar' => ['title' => 'مقال جديد', 'slug' => 'مقال-جديد', 'summary' => '', 'body_markdown' => 'نص.'],
                'en' => ['title' => 'New article', 'slug' => 'new-article', 'summary' => 'Short.', 'body_markdown' => 'Text.'],
            ],
        ])->assertRedirect();

        $article = Article::query()->where('key', 'new-article')->firstOrFail();
        $this->assertCount(2, $article->translations);
        $this->assertSame('مقال-جديد', $article->translationFor('ar')?->slug);
        $this->assertSame($this->editor->id, $article->translationFor('en')?->author_id);
        $this->assertCount(1, $article->sources);
    }

    public function test_article_validation_rejects_duplicate_slugs_and_missing_languages(): void
    {
        $existing = $this->draft();

        $this->actingAs($this->editor)->post('/admin/content/articles', [
            'key' => 'another',
            'content_category_id' => $this->category->id,
            'kind' => 'article',
            'translations' => ['en' => ['title' => 'Dup', 'slug' => $existing->slug, 'body_markdown' => 'x']],
        ])->assertSessionHasErrors('translations.en.slug');

        $this->actingAs($this->editor)->post('/admin/content/articles', [
            'key' => 'empty',
            'content_category_id' => $this->category->id,
            'kind' => 'article',
            'translations' => ['ar' => ['title' => ''], 'en' => ['title' => '']],
        ])->assertSessionHasErrors('translations');
    }

    public function test_category_slugs_cannot_use_reserved_words(): void
    {
        $this->actingAs($this->editor)->post('/admin/content/categories', [
            'key' => 'reserved',
            'icon' => 'info',
            'translations' => [
                'ar' => ['name' => 'محجوز', 'slug' => 'article'],
                'en' => ['name' => 'Reserved', 'slug' => 'search'],
            ],
        ])->assertSessionHasErrors(['translations.ar.slug', 'translations.en.slug']);
    }

    public function test_reviewer_dashboard_lists_reviews_they_are_allowed_to_approve(): void
    {
        $translation = $this->draft();
        app(ArticleWorkflow::class)->submit($translation, $this->editor);

        $this->actingAs($this->reviewer)->get('/admin')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('summary.pendingReviewsCount', 1)
            ->has('tasks', 1)
            ->where('tasks.0.kind', 'review'));

        $ownDraft = $this->draft(author: $this->reviewer);
        app(ArticleWorkflow::class)->submit($ownDraft, $this->reviewer);

        $this->actingAs($this->reviewer)->get('/admin')->assertInertia(fn (AssertableInertia $page) => $page->has('tasks', 1));
    }

    public function test_preview_uses_the_sanitising_renderer(): void
    {
        $this->actingAs($this->editor)
            ->postJson('/admin/content/preview', ['markdown' => "## Hi\n\n<script>x</script>"])
            ->assertOk()
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, '<h2>Hi</h2>') && ! str_contains($html, '<script'));
    }

    public function test_source_management_is_limited_to_editors_and_super_admins(): void
    {
        $payload = ['title' => 'NHS page', 'publisher' => 'NHS', 'url' => 'https://www.nhs.uk/', 'evidence_type' => 'public_health_body'];

        $this->actingAs($this->reviewer)->post('/admin/content/sources', $payload)->assertForbidden();
        $this->actingAs($this->editor)->post('/admin/content/sources', $payload)->assertRedirect();
        $this->actingAs($this->editor)->post('/admin/content/sources', [...$payload, 'url' => 'javascript:alert(1)'])->assertSessionHasErrors('url');

        $this->assertDatabaseHas('sources', ['title' => 'NHS page']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'source.created']);
    }
}
