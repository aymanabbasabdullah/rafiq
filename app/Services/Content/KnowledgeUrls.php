<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\ArticleTranslation;
use App\Models\ContentCategory;

/**
 * Public URLs for Knowledge Center pages. Slugs differ per language, so links are always
 * built from the published revision and the category translation for that language.
 */
class KnowledgeUrls
{
    public function category(ContentCategory $category, string $locale): ?string
    {
        $translation = $category->translationFor($locale);

        return $translation !== null ? route('learn.category', ['locale' => $locale, 'category' => $translation->slug]) : null;
    }

    public function article(ArticleTranslation $translation): ?string
    {
        $translation->loadMissing(['publishedRevision', 'article.category.translations']);
        $revision = $translation->publishedRevision;
        $category = $translation->article->category->translationFor($translation->locale);

        if ($revision === null || $category === null || ! $translation->isLive()) {
            return null;
        }

        return route('learn.article', ['locale' => $translation->locale, 'category' => $category->slug, 'slug' => $revision->slug]);
    }

    /**
     * Stable language-neutral link: redirects to the article, or explains it is not available in that language (FR-072).
     */
    public function articleByKey(string $key, string $locale): string
    {
        return route('learn.article.key', ['locale' => $locale, 'key' => $key]);
    }
}
