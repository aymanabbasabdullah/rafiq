<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\Journey;
use App\Enums\Locale;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\SearchKnowledgeRequest;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\ContentCategory;
use App\Models\ContentCategoryTranslation;
use App\Services\Analytics\MetricsRecorder;
use App\Services\Content\ContentSearch;
use App\Services\Content\KnowledgeCatalog;
use App\Services\Content\KnowledgeUrls;
use App\Support\LocalizedUrls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Knowledge Center (Module-08): home (J1 entry), topics, articles and search.
 *
 * Visitors only ever see published, medically approved revisions in their own language;
 * there is no silent fallback to another language (FR-072, L10N-010).
 */
class KnowledgeController extends Controller
{
    public function __construct(private readonly KnowledgeUrls $urls, private readonly KnowledgeCatalog $catalog) {}

    public function index(Request $request, MetricsRecorder $metrics): Response
    {
        $locale = App::getLocale();

        $this->recordJourneyOnce($request, $metrics);
        $metrics->increment('page_view', 'learn_index');

        return Inertia::render('Public/Learn/Index', [
            'categories' => $this->catalog->categories($locale),
            'latest' => $this->catalog->latest($locale, 6),
            'searchUrl' => route('learn.search'),
            'glossaryUrl' => route('glossary.index'),
            'concernUrl' => Journey::Concern->url(),
            'otherJourneys' => Journey::cards(exclude: Journey::Learn),
        ]);
    }

    public function category(string $locale, string $category): Response
    {
        $categoryTranslation = $this->findCategory($locale, $category);
        $model = $categoryTranslation->category;

        abort_unless($model->is_visible, 404);

        $model->load('translations');
        LocalizedUrls::override(request(), $this->categoryAlternates($model));

        return Inertia::render('Public/Learn/Category', [
            'category' => [
                'name' => $categoryTranslation->name,
                'description' => $categoryTranslation->description,
                'icon' => $model->icon,
            ],
            'articles' => $this->catalog->articleCards(
                $this->catalog->liveTranslations($locale)
                    ->whereHas('article', fn ($query) => $query->where('content_category_id', $model->id))
                    ->get()
                    ->sortBy(fn (ArticleTranslation $translation): int => $translation->article->sort_order)
                    ->values(),
            ),
            'learnUrl' => route('journeys.learn'),
        ]);
    }

    public function article(Request $request, MetricsRecorder $metrics, string $locale, string $category, string $slug): Response
    {
        $categoryTranslation = $this->findCategory($locale, $category);

        $translation = ArticleTranslation::query()
            ->live($locale)
            ->whereHas('publishedRevision', fn ($query) => $query->where('slug', $slug))
            ->whereHas('article', fn ($query) => $query->where('content_category_id', $categoryTranslation->content_category_id))
            ->with(['publishedRevision.approver', 'article.sources', 'article.category.translations', 'article.translations.publishedRevision'])
            ->first();

        abort_if($translation === null || $translation->publishedRevision === null, 404);

        $metrics->increment('page_view', 'article');

        $article = $translation->article;
        $revision = $translation->publishedRevision;

        LocalizedUrls::override($request, $this->articleAlternates($article));

        return Inertia::render('Public/Learn/Article', [
            'article' => [
                'key' => $article->key,
                'title' => $revision->title,
                'summary' => $revision->summary,
                'bodyHtml' => $revision->body_html,
                'version' => $revision->version,
                'approvedAt' => $revision->approved_at->toDateString(),
                'reviewDueAt' => $translation->review_due_at?->toDateString(),
                'aiEnabled' => $article->ai_enabled && $article->category->ai_enabled,
            ],
            'category' => [
                'name' => $categoryTranslation->name,
                'url' => route('learn.category', ['locale' => $locale, 'category' => $categoryTranslation->slug]),
            ],
            'sources' => $article->sources->map(fn ($source): array => [
                'id' => $source->id,
                'title' => $source->title,
                'publisher' => $source->publisher,
                'url' => $source->url,
                'date' => $source->publication_date,
            ])->values(),
            'related' => $this->catalog->articleCards(
                $this->catalog->liveTranslations($locale)
                    ->where('id', '!=', $translation->id)
                    ->whereHas('article', fn ($query) => $query->where('content_category_id', $article->content_category_id))
                    ->limit(3)
                    ->get(),
            ),
            'learnUrl' => route('journeys.learn'),
        ]);
    }

    /**
     * Language-neutral article link used by the language switcher (FR-072).
     */
    public function articleByKey(Request $request, string $locale, string $key): Response|RedirectResponse
    {
        $article = Article::query()->where('key', $key)->with(['translations.publishedRevision', 'category.translations'])->firstOrFail();
        $translation = $article->translationFor($locale);

        if ($translation !== null && ($url = $this->urls->article($translation)) !== null) {
            return redirect()->to($url);
        }

        $available = collect(Locale::cases())
            ->map(fn (Locale $other): ?array => ($candidate = $article->translationFor($other->value)) !== null && ($url = $this->urls->article($candidate)) !== null
                ? ['locale' => $other->value, 'name' => $other->nativeName(), 'title' => $candidate->publishedRevision?->title, 'url' => $url]
                : null)
            ->filter()
            ->values();

        abort_if($available->isEmpty(), 404);

        LocalizedUrls::override($request, $this->articleAlternates($article));

        return Inertia::render('Public/Learn/Unavailable', [
            'available' => $available,
            'learnUrl' => route('journeys.learn'),
        ]);
    }

    public function search(SearchKnowledgeRequest $request, ContentSearch $search): JsonResponse
    {
        return response()->json([
            'results' => $search->search(App::getLocale(), (string) $request->validated('query')),
        ]);
    }

    private function findCategory(string $locale, string $slug): ContentCategoryTranslation
    {
        return ContentCategoryTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->with('category')
            ->firstOrFail();
    }

    /**
     * @return array<string, string>
     */
    private function articleAlternates(Article $article): array
    {
        $urls = [];

        foreach (Locale::cases() as $locale) {
            $translation = $article->translationFor($locale->value);
            $urls[$locale->value] = ($translation !== null ? $this->urls->article($translation) : null)
                ?? $this->urls->articleByKey($article->key, $locale->value);
        }

        return $urls;
    }

    /**
     * @return array<string, string>
     */
    private function categoryAlternates(ContentCategory $category): array
    {
        $urls = [];

        foreach (Locale::cases() as $locale) {
            $urls[$locale->value] = $this->urls->category($category, $locale->value) ?? route('journeys.learn', ['locale' => $locale->value]);
        }

        return $urls;
    }

    private function recordJourneyOnce(Request $request, MetricsRecorder $metrics): void
    {
        $key = 'journeys_selected.'.Journey::Learn->value;

        if ($request->session()->get($key) !== true) {
            $request->session()->put($key, true);
            $metrics->increment('journey_selected', Journey::Learn->value);
        }
    }
}
