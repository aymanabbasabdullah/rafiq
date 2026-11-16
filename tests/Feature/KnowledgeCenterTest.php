<?php

namespace Tests\Feature;

use App\Enums\ArticleKind;
use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\ContentCategory;
use App\Models\ContentChunk;
use App\Models\GlossaryTerm;
use App\Models\Source;
use App\Models\User;
use App\Services\Content\ArabicNormalizer;
use App\Services\Content\ArticleWorkflow;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Module-08: public Knowledge Center — published revisions only, per-language slugs, search, glossary.
 */
class KnowledgeCenterTest extends TestCase
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
        $this->category = ContentCategory::factory()->translated()->create(['key' => 'warning-signs']);
        $this->withSession(['age_confirmed' => true]);
    }

    /**
     * @param  array<string, array{title: string, slug: string, summary?: ?string, body_markdown: string}>  $translations
     */
    private function publishedArticle(array $translations, string $key = 'pain-guide'): Article
    {
        $workflow = app(ArticleWorkflow::class);
        $article = Article::factory()->create(['key' => $key, 'content_category_id' => $this->category->id]);
        $article->sources()->attach(Source::factory()->create()->id, ['support_level' => 'direct']);

        foreach ($translations as $locale => $data) {
            $translation = $workflow->saveDraft($article, $locale, ['summary' => null, ...$data], $this->editor);
            $workflow->submit($translation, $this->editor);
            $workflow->approve($translation->refresh(), $this->reviewer);
            $workflow->publish($translation->refresh(), $this->editor);
        }

        return $article->refresh();
    }

    private function articleUrl(string $locale, string $slug): string
    {
        $categorySlug = $this->category->translations()->where('locale', $locale)->value('slug');

        return route('learn.article', ['locale' => $locale, 'category' => $categorySlug, 'slug' => $slug]);
    }

    public function test_knowledge_center_requires_the_age_gate(): void
    {
        $this->withSession(['age_confirmed' => null]);
        session()->forget('age_confirmed');

        $this->get('/en/learn')->assertRedirect();
        $this->get('/en/glossary')->assertRedirect();
    }

    public function test_home_lists_topics_with_live_article_counts_and_latest_articles(): void
    {
        $this->publishedArticle(['en' => ['title' => 'Pain guide', 'slug' => 'pain-guide', 'body_markdown' => '## A\n\nText.']]);

        $this->get('/en/learn')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Learn/Index')
                ->has('categories', 1)
                ->where('categories.0.count', 1)
                ->has('latest', 1)
                ->where('latest.0.title', 'Pain guide')
                ->where('searchUrl', route('learn.search', ['locale' => 'en'])));

        $this->assertDatabaseHas('daily_metrics', ['metric' => 'journey_selected', 'dimension' => 'learn']);
    }

    public function test_landing_previews_live_topics_and_the_latest_article_in_the_visitor_language_only(): void
    {
        $this->publishedArticle(['en' => ['title' => 'Pain guide', 'slug' => 'pain-guide', 'body_markdown' => '## A\n\nText.']]);

        $this->get('/en')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Landing')
            ->where('knowledge.categories.0.count', 1)
            ->where('knowledge.featured.title', 'Pain guide')
            ->where('knowledge.featured.url', $this->articleUrl('en', 'pain-guide'))
            ->where('links.learn', route('journeys.learn', ['locale' => 'en'])));

        $this->get('/ar')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('knowledge.categories.0.count', 0)
            ->where('knowledge.featured', null));
    }

    public function test_landing_hides_the_knowledge_preview_when_the_learn_journey_is_disabled(): void
    {
        config(['rafiq.journeys.enabled.learn' => false]);

        $this->get('/en')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('knowledge', null)
            ->where('links.learn', null)
            ->where('links.glossary', null));
    }

    public function test_article_page_shows_the_approved_revision_with_sources_and_language_alternates(): void
    {
        $this->publishedArticle([
            'ar' => ['title' => 'دليل الألم', 'slug' => 'دليل-الالم', 'body_markdown' => "## متى\n\nنص عربي."],
            'en' => ['title' => 'Pain guide', 'slug' => 'pain-guide', 'body_markdown' => "## When\n\nEnglish text."],
        ]);

        $response = $this->get($this->articleUrl('en', 'pain-guide'));

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Learn/Article')
                ->where('article.title', 'Pain guide')
                ->where('article.version', 1)
                ->where('article.bodyHtml', fn (string $html): bool => str_contains($html, '<h2>When</h2>'))
                ->has('sources', 1)
                ->where('locale.alternate.url', $this->articleUrl('ar', 'دليل-الالم')));

        $response->assertSee('hreflang="ar" href="'.e($this->articleUrl('ar', 'دليل-الالم')).'"', false);
        // x-default always points at the platform default language, even on English pages.
        $response->assertSee('hreflang="x-default" href="'.e($this->articleUrl('ar', 'دليل-الالم')).'"', false);
    }

    public function test_editing_a_published_article_keeps_the_live_version_until_it_is_republished(): void
    {
        $article = $this->publishedArticle(['en' => ['title' => 'Original title', 'slug' => 'original', 'body_markdown' => 'Original body.']]);
        $workflow = app(ArticleWorkflow::class);

        $translation = $workflow->saveDraft($article, 'en', ['title' => 'Edited title', 'slug' => 'original', 'summary' => null, 'body_markdown' => 'Edited body.'], $this->editor);

        $this->assertSame(ReviewStatus::Draft, $translation->review_status);
        $this->get($this->articleUrl('en', 'original'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('article.title', 'Original title'));

        $workflow->submit($translation, $this->editor);
        $workflow->approve($translation->refresh(), $this->reviewer);
        $workflow->publish($translation->refresh(), $this->editor);

        $this->get($this->articleUrl('en', 'original'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('article.title', 'Edited title')->where('article.version', 2));
    }

    public function test_drafts_and_hidden_articles_are_never_public(): void
    {
        $article = $this->publishedArticle(['en' => ['title' => 'Visible', 'slug' => 'visible', 'body_markdown' => 'Body.']]);
        $translation = $article->translationFor('en');

        app(ArticleWorkflow::class)->hide($translation, $this->editor);

        $this->get($this->articleUrl('en', 'visible'))->assertNotFound();
        $this->get('/en/learn')->assertInertia(fn (AssertableInertia $page) => $page->has('latest', 0));
    }

    public function test_missing_language_shows_an_explicit_unavailable_page_instead_of_a_silent_fallback(): void
    {
        $this->publishedArticle(['ar' => ['title' => 'عربي فقط', 'slug' => 'عربي-فقط', 'body_markdown' => 'نص.']], 'arabic-only');

        $this->get(route('learn.article.key', ['locale' => 'en', 'key' => 'arabic-only']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Learn/Unavailable')
                ->has('available', 1)
                ->where('available.0.locale', 'ar'));

        $this->get(route('learn.article.key', ['locale' => 'ar', 'key' => 'arabic-only']))
            ->assertRedirect($this->articleUrl('ar', 'عربي-فقط'));
    }

    public function test_hidden_topics_are_not_reachable(): void
    {
        $this->category->update(['is_visible' => false]);
        $slug = $this->category->translations()->where('locale', 'en')->value('slug');

        $this->get(route('learn.category', ['locale' => 'en', 'category' => $slug]))->assertNotFound();
    }

    public function test_search_matches_arabic_spelling_variants_and_returns_only_live_content(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->markTestSkipped('InnoDB FULLTEXT cannot see uncommitted rows; covered by KnowledgeSearchMysqlTest.');
        }

        $this->publishedArticle(['ar' => ['title' => 'ألم الخصية المفاجئ', 'slug' => 'الم-مفاجئ', 'body_markdown' => 'إذا كان الألمُ شديداً فاذهب إلى الطوارئ.']]);

        $draft = Article::factory()->create(['content_category_id' => $this->category->id]);
        app(ArticleWorkflow::class)->saveDraft($draft, 'ar', ['title' => 'مسودة عن الألم', 'slug' => 'مسودة', 'summary' => null, 'body_markdown' => 'الالم'], $this->editor);

        $this->postJson('/ar/learn/search', ['query' => 'الالم'])
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.title', 'ألم الخصية المفاجئ');
    }

    public function test_search_queries_are_validated_and_never_accepted_via_get(): void
    {
        $this->postJson('/en/learn/search', ['query' => 'a'])->assertUnprocessable();
        $this->postJson('/en/learn/search', ['query' => str_repeat('x', 101)])->assertUnprocessable();
        // GET resolves to a (non-existent) topic called "search"; the query is never processed.
        $this->get('/en/learn/search?query=pain')->assertNotFound();
    }

    public function test_published_html_is_sanitised(): void
    {
        $html = app(MarkdownRenderer::class)->toHtml("Hello <script>alert(1)</script>\n\n[bad](javascript:alert(1)) [ok](https://www.nhs.uk/)\n\n<img src=x onerror=alert(1)>");

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function test_publishing_replaces_search_chunks_with_the_new_revision(): void
    {
        $article = $this->publishedArticle(['en' => ['title' => 'Chunks', 'slug' => 'chunks', 'body_markdown' => "## One\n\nFirst.\n\n## Two\n\nSecond."]]);
        $translation = $article->translationFor('en');
        $firstRevision = $translation->published_revision_id;

        $this->assertSame(3, ContentChunk::query()->where('article_translation_id', $translation->id)->count());

        $workflow = app(ArticleWorkflow::class);
        $workflow->saveDraft($article, 'en', ['title' => 'Chunks', 'slug' => 'chunks', 'summary' => null, 'body_markdown' => 'Only one paragraph.'], $this->editor);
        $workflow->submit($translation->refresh(), $this->editor);
        $workflow->approve($translation->refresh(), $this->reviewer);
        $workflow->publish($translation->refresh(), $this->editor);

        $chunks = ContentChunk::query()->where('article_translation_id', $translation->id)->get();
        $this->assertCount(2, $chunks);
        $this->assertTrue($chunks->every(fn (ContentChunk $chunk): bool => $chunk->article_revision_id !== $firstRevision));
    }

    public function test_arabic_normalizer_unifies_letter_variants_and_removes_diacritics(): void
    {
        $normalizer = new ArabicNormalizer;

        $this->assertSame('الالم في الخصيه', $normalizer->normalize('الألمُ في الخِصيةِ'));
        $this->assertSame($normalizer->normalize('إبرة'), $normalizer->normalize('ابره'));
        $this->assertSame('pain test', $normalizer->normalize('Pain, TEST!'));
    }

    public function test_glossary_lists_only_approved_published_terms_alphabetically(): void
    {
        foreach (['Varicocele' => [ReviewStatus::Approved, PublishStatus::Published], 'Infertility' => [ReviewStatus::Approved, PublishStatus::Published], 'Draft term' => [ReviewStatus::Draft, PublishStatus::Draft]] as $label => [$review, $publish]) {
            GlossaryTerm::factory()->create()->translations()->create([
                'locale' => 'en', 'term' => $label, 'definition_markdown' => 'x', 'definition_html' => '<p>x</p>',
                'review_status' => $review, 'publish_status' => $publish,
            ]);
        }

        $this->get('/en/glossary')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/Learn/Glossary')
                ->has('terms', 2)
                ->where('terms.0.term', 'Infertility')
                ->where('terms.1.term', 'Varicocele'));
    }

    public function test_platform_guides_do_not_need_a_source(): void
    {
        $article = Article::factory()->platformGuide()->create(['content_category_id' => $this->category->id]);
        $workflow = app(ArticleWorkflow::class);
        $translation = $workflow->saveDraft($article, 'en', ['title' => 'How it works', 'slug' => 'how', 'summary' => null, 'body_markdown' => 'Body.'], $this->editor);

        $workflow->submit($translation, $this->editor);

        $this->assertSame(ReviewStatus::PendingMedicalReview, $translation->refresh()->review_status);
        $this->assertSame(ArticleKind::PlatformGuide, $article->kind);
        $this->assertSame(0, ArticleTranslation::query()->where('publish_status', 'published')->count());
    }
}
