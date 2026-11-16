<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\ArticleTranslation;
use App\Models\ContentCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Read model for the public Knowledge Center: visible topics and live article cards in one
 * language. Shared by the Knowledge Center pages and the landing page preview.
 */
class KnowledgeCatalog
{
    public function __construct(private readonly KnowledgeUrls $urls) {}

    /**
     * Visible topics that have a translation in this language, with their live article counts.
     *
     * @return list<array{key: string, icon: string, name: string, description: string|null, url: string, count: int}>
     */
    public function categories(string $locale): array
    {
        return ContentCategory::query()
            ->where('is_visible', true)
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ContentCategory $category): ?array => $this->categoryCard($category, $locale))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * The most recently published articles in this language.
     *
     * @return list<array{title: string, summary: string|null, url: string, category: string|null}>
     */
    public function latest(string $locale, int $limit): array
    {
        return $this->articleCards($this->liveTranslations($locale)->latest('published_at')->limit($limit)->get());
    }

    /**
     * Live (published, approved) translations in visible topics.
     *
     * @return Builder<ArticleTranslation>
     */
    public function liveTranslations(string $locale): Builder
    {
        return ArticleTranslation::query()
            ->live($locale)
            ->whereHas('article.category', fn ($query) => $query->where('is_visible', true))
            ->with(['publishedRevision', 'article.category.translations']);
    }

    /**
     * @param  Collection<int, ArticleTranslation>  $translations
     * @return list<array{title: string, summary: string|null, url: string, category: string|null}>
     */
    public function articleCards(Collection $translations): array
    {
        $cards = [];

        foreach ($translations as $translation) {
            $url = $this->urls->article($translation);

            if ($url === null || $translation->publishedRevision === null) {
                continue;
            }

            $cards[] = [
                'title' => $translation->publishedRevision->title,
                'summary' => $translation->publishedRevision->summary,
                'url' => $url,
                'category' => $translation->article->category->translationFor($translation->locale)?->name,
            ];
        }

        return $cards;
    }

    /**
     * @return array{key: string, icon: string, name: string, description: string|null, url: string, count: int}|null
     */
    private function categoryCard(ContentCategory $category, string $locale): ?array
    {
        $translation = $category->translationFor($locale);

        if ($translation === null) {
            return null;
        }

        return [
            'key' => $category->key,
            'icon' => $category->icon,
            'name' => $translation->name,
            'description' => $translation->description,
            'url' => route('learn.category', ['locale' => $locale, 'category' => $translation->slug]),
            'count' => ArticleTranslation::query()->live($locale)->whereHas('article', fn ($query) => $query->where('content_category_id', $category->id))->count(),
        ];
    }
}
