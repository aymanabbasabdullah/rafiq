<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Enums\Locale;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\SaveCategoryRequest;
use App\Models\ContentCategory;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Knowledge Center topics with Arabic/English names and slugs (M11B-FR-01).
 */
class CategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ContentCategory::class);

        return Inertia::render('Admin/Content/Categories', [
            'categories' => ContentCategory::query()->with('translations')->withCount('articles')->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (ContentCategory $category): array => [
                    'id' => $category->id,
                    'key' => $category->key,
                    'icon' => $category->icon,
                    'sort_order' => $category->sort_order,
                    'is_visible' => $category->is_visible,
                    'ai_enabled' => $category->ai_enabled,
                    'articlesCount' => $category->articles_count,
                    'translations' => collect(Locale::values())->mapWithKeys(fn (string $locale): array => [$locale => [
                        'name' => $category->translationFor($locale)?->name ?? '',
                        'slug' => $category->translationFor($locale)?->slug ?? '',
                        'description' => $category->translationFor($locale)?->description ?? '',
                    ]]),
                    'updateUrl' => route('admin.content.categories.update', $category),
                ]),
            'icons' => SaveCategoryRequest::ICONS,
            'storeUrl' => route('admin.content.categories.store'),
            'indexUrl' => route('admin.content.index'),
            'canManage' => $request->user()?->can('create', ContentCategory::class) ?? false,
        ]);
    }

    public function store(SaveCategoryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $category = ContentCategory::query()->create([
                'key' => (string) $request->validated('key'),
                ...$this->attributes($request),
            ]);

            $this->saveTranslations($category, $request);
            $this->audit->record('content_category.created', $category, ['key' => $category->key]);
        });

        return back()->with('success', __('admin.content.saved'));
    }

    public function update(SaveCategoryRequest $request, ContentCategory $category): RedirectResponse
    {
        DB::transaction(function () use ($request, $category): void {
            $category->update($this->attributes($request));
            $this->saveTranslations($category, $request);
            $this->audit->record('content_category.updated', $category, ['key' => $category->key]);
        });

        return back()->with('success', __('admin.content.saved'));
    }

    /**
     * @return array{icon: string, sort_order: int, is_visible: bool, ai_enabled: bool}
     */
    private function attributes(SaveCategoryRequest $request): array
    {
        return [
            'icon' => (string) $request->validated('icon'),
            'sort_order' => (int) ($request->validated('sort_order') ?? 0),
            'is_visible' => $request->boolean('is_visible'),
            'ai_enabled' => $request->boolean('ai_enabled'),
        ];
    }

    private function saveTranslations(ContentCategory $category, SaveCategoryRequest $request): void
    {
        foreach (Locale::values() as $locale) {
            /** @var array{name: string, slug: string, description?: ?string} $data */
            $data = $request->validated("translations.{$locale}");

            $category->translations()->updateOrCreate(['locale' => $locale], [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
            ]);
        }
    }
}
