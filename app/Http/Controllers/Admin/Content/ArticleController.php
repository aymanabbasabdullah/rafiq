<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Enums\ArticleKind;
use App\Enums\Locale;
use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Enums\SourceSupport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\SaveArticleRequest;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\ContentCategory;
use App\Models\Source;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Content\ArticleWorkflow;
use App\Services\Content\KnowledgeUrls;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin article management with bilingual working copies (M11B-FR-01..FR-03).
 */
class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleWorkflow $workflow,
        private readonly AuditLogger $audit,
        private readonly KnowledgeUrls $urls,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Article::class);

        $status = (string) $request->query('status', '');

        $articles = Article::query()
            ->with(['category.translations', 'translations'])
            ->when($status !== '', fn ($query) => $query->whereHas('translations', fn ($sub) => $status === 'published'
                ? $sub->where('publish_status', PublishStatus::Published->value)
                : $sub->where('review_status', $status)))
            ->orderBy('content_category_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Article $article): array => [
                'id' => $article->id,
                'key' => $article->key,
                'kind' => $article->kind->value,
                'title' => $article->translationFor($request->user()?->preferred_locale ?? 'ar')?->title
                    ?? $article->translations->first()?->title
                    ?? $article->key,
                'category' => $article->category->translationFor(app()->getLocale())?->name ?? $article->category->key,
                'translations' => collect(Locale::values())->mapWithKeys(fn (string $locale): array => [
                    $locale => $this->statusSummary($article->translationFor($locale)),
                ]),
                'editUrl' => route('admin.content.articles.edit', $article),
            ]);

        return Inertia::render('Admin/Content/Index', [
            'articles' => $articles,
            'filters' => ['status' => $status],
            'counts' => [
                'pending' => ArticleTranslation::query()->where('review_status', ReviewStatus::PendingMedicalReview->value)->count(),
                'published' => ArticleTranslation::query()->where('publish_status', PublishStatus::Published->value)->count(),
                'dueSoon' => ArticleTranslation::query()->where('publish_status', PublishStatus::Published->value)
                    ->whereNotNull('review_due_at')->whereDate('review_due_at', '<=', now()->addDays(30))->count(),
            ],
            'createUrl' => route('admin.content.articles.create'),
            'indexUrl' => route('admin.content.index'),
            'canCreate' => $request->user()?->can('create', Article::class) ?? false,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Article::class);

        return Inertia::render('Admin/Content/Edit', [
            ...$this->formOptions(),
            'article' => null,
            'submitUrl' => route('admin.content.articles.store'),
        ]);
    }

    public function store(SaveArticleRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $article = DB::transaction(function () use ($request, $user): Article {
            $article = Article::query()->create([
                'content_category_id' => (int) $request->validated('content_category_id'),
                'key' => (string) $request->validated('key'),
                'kind' => (string) $request->validated('kind'),
                'ai_enabled' => $request->boolean('ai_enabled'),
                'sort_order' => (int) ($request->validated('sort_order') ?? 0),
                'created_by' => $user->id,
            ]);

            $this->syncSources($article, $request);
            $this->audit->record('article.created', $article, ['key' => $article->key], $user);

            foreach ($request->filledTranslations() as $locale => $data) {
                $this->workflow->saveDraft($article, $locale, $data, $user);
            }

            return $article;
        });

        return redirect()->route('admin.content.articles.edit', $article)->with('success', __('admin.content.saved'));
    }

    public function edit(Request $request, Article $article): Response
    {
        Gate::authorize('update', $article);

        /** @var User $user */
        $user = $request->user();
        $article->load(['translations.revisions', 'translations.publishedRevision', 'sources', 'category.translations']);

        return Inertia::render('Admin/Content/Edit', [
            ...$this->formOptions(),
            'article' => [
                'id' => $article->id,
                'key' => $article->key,
                'kind' => $article->kind->value,
                'contentCategoryId' => $article->content_category_id,
                'aiEnabled' => $article->ai_enabled,
                'sortOrder' => $article->sort_order,
                'sources' => $article->sources->map(fn (Source $source): array => [
                    'id' => $source->id,
                    'support_level' => (string) $source->pivot?->getAttribute('support_level'),
                    'claim_note' => $source->pivot?->getAttribute('claim_note'),
                ])->values(),
                'translations' => collect(Locale::values())->mapWithKeys(fn (string $locale): array => [
                    $locale => $this->translationPayload($article->translationFor($locale), $user),
                ]),
            ],
            'submitUrl' => route('admin.content.articles.update', $article),
        ]);
    }

    public function update(SaveArticleRequest $request, Article $article): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($request, $article, $user): void {
            $article->fill([
                'content_category_id' => (int) $request->validated('content_category_id'),
                'kind' => (string) $request->validated('kind'),
                'ai_enabled' => $request->boolean('ai_enabled'),
                'sort_order' => (int) ($request->validated('sort_order') ?? 0),
            ]);

            if ($article->isDirty()) {
                $changed = array_keys($article->getDirty());
                $article->save();
                $this->audit->record('article.updated', $article, ['fields' => $changed], $user);
            }

            $this->syncSources($article, $request);

            foreach ($request->filledTranslations() as $locale => $data) {
                $this->workflow->saveDraft($article, $locale, $data, $user);
            }
        });

        return back()->with('success', __('admin.content.saved'));
    }

    private function syncSources(Article $article, SaveArticleRequest $request): void
    {
        /** @var list<array{id: int, support_level: string, claim_note?: ?string}> $sources */
        $sources = $request->validated('sources') ?? [];
        $sync = [];

        foreach ($sources as $source) {
            $sync[(int) $source['id']] = ['support_level' => $source['support_level'], 'claim_note' => $source['claim_note'] ?? null];
        }

        $before = $article->sources()->pluck('sources.id')->sort()->values()->all();
        $article->sources()->sync($sync);
        $after = collect(array_keys($sync))->sort()->values()->all();

        if ($before !== $after) {
            $this->audit->record('article.sources.updated', $article, ['sources' => $after]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => ContentCategory::query()->with('translations')->orderBy('sort_order')->get()->map(fn (ContentCategory $category): array => [
                'id' => $category->id,
                'name' => $category->translationFor(app()->getLocale())?->name ?? $category->key,
            ])->values(),
            'sourceOptions' => Source::query()->orderBy('title')->get(['id', 'title', 'publisher'])->map(fn (Source $source): array => [
                'id' => $source->id,
                'label' => "{$source->title} — {$source->publisher}",
            ])->values(),
            'kinds' => ArticleKind::values(),
            'supportLevels' => SourceSupport::values(),
            'indexUrl' => route('admin.content.index'),
            'sourcesUrl' => route('admin.content.sources.index'),
            'previewUrl' => route('admin.content.preview'),
        ];
    }

    /**
     * @return array{review: string, publish: string, live: bool}|null
     */
    private function statusSummary(?ArticleTranslation $translation): ?array
    {
        if ($translation === null) {
            return null;
        }

        return [
            'review' => $translation->review_status->value,
            'publish' => $translation->publish_status->value,
            'live' => $translation->isLive(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function translationPayload(?ArticleTranslation $translation, User $user): ?array
    {
        if ($translation === null) {
            return null;
        }

        $latest = $translation->revisions->first();
        $canPublish = $user->can('publish', Article::class);
        $canReview = $translation->review_status === ReviewStatus::PendingMedicalReview
            && Gate::forUser($user)->allows('four-eyes', [$translation->author_id, $translation->last_editor_id]);

        return [
            'id' => $translation->id,
            'title' => $translation->title,
            'slug' => $translation->slug,
            'summary' => $translation->summary,
            'body_markdown' => $translation->body_markdown,
            'reviewStatus' => $translation->review_status->value,
            'publishStatus' => $translation->publish_status->value,
            'live' => $translation->isLive(),
            'version' => $translation->version,
            'reviewComment' => $translation->review_comment,
            'reviewDueAt' => $translation->review_due_at?->toDateString(),
            'publicUrl' => $this->urls->article($translation->loadMissing('article.category.translations')),
            'latestApproved' => $latest !== null ? ['version' => $latest->version, 'title' => $latest->title, 'body_markdown' => $latest->body_markdown] : null,
            'liveIsLatest' => $latest !== null && $translation->published_revision_id === $latest->id,
            'can' => [
                'submit' => $translation->review_status === ReviewStatus::Draft,
                'approve' => $canReview,
                'reject' => $canReview,
                'publish' => $canPublish && $translation->review_status === ReviewStatus::Approved
                    && ($latest !== null && $translation->published_revision_id !== $latest->id || ! $translation->isLive()),
                'hide' => $canPublish && $translation->isLive(),
                'archive' => $canPublish && $translation->publish_status !== PublishStatus::Archived,
            ],
            'actionUrl' => route('admin.content.translations.action', ['translation' => $translation->id, 'action' => '__action__']),
        ];
    }
}
