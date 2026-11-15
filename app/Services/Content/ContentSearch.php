<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\ArticleTranslation;
use App\Models\ContentChunk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Searches published content in one language (FR-070, M08-FR-06).
 *
 * MySQL uses the FULLTEXT ngram index; other drivers (SQLite in tests) fall back to
 * matching every normalised term. Queries are never stored or logged (PRIV-003).
 */
class ContentSearch
{
    private const LIMIT = 10;

    public function __construct(
        private readonly ArabicNormalizer $normalizer,
        private readonly KnowledgeUrls $urls,
    ) {}

    /**
     * @return list<array{title: string, summary: string|null, url: string}>
     */
    public function search(string $locale, string $query): array
    {
        $normalized = $this->normalizer->normalize($query);

        if (mb_strlen($normalized) < 2) {
            return [];
        }

        $translationIds = DB::getDriverName() === 'mysql'
            ? $this->fullTextMatches($locale, $normalized)
            : $this->termMatches($locale, $normalized);

        if ($translationIds === []) {
            return [];
        }

        $translations = ArticleTranslation::query()
            ->live($locale)
            ->whereIn('id', $translationIds)
            ->with(['publishedRevision', 'article.category.translations'])
            ->get()
            ->sortBy(fn (ArticleTranslation $translation): int|false => array_search($translation->id, $translationIds, true));

        $results = [];

        foreach ($translations as $translation) {
            $url = $this->urls->article($translation);

            if ($url !== null && $translation->publishedRevision !== null) {
                $results[] = [
                    'title' => $translation->publishedRevision->title,
                    'summary' => $translation->publishedRevision->summary,
                    'url' => $url,
                ];
            }
        }

        return $results;
    }

    /**
     * @return list<int>
     */
    /**
     * Boolean mode with every term as a required phrase. With the ngram parser, natural-language
     * mode matches almost everything (any shared 2-gram); required phrases give precise substring matching.
     */
    private function fullTextMatches(string $locale, string $normalized): array
    {
        $terms = array_values(array_filter(explode(' ', $normalized), fn (string $term): bool => mb_strlen($term) >= 2));

        if ($terms === []) {
            return [];
        }

        // Normalised terms contain only letters/digits, so wrapping them in quotes is safe.
        $boolean = implode(' ', array_map(fn (string $term): string => '+"'.$term.'"', $terms));

        return $this->liveChunks($locale)
            ->selectRaw('content_chunks.article_translation_id, MAX(MATCH(content_chunks.heading, content_chunks.text_normalized) AGAINST (? IN BOOLEAN MODE)) AS score', [$boolean])
            ->whereRaw('MATCH(content_chunks.heading, content_chunks.text_normalized) AGAINST (? IN BOOLEAN MODE)', [$boolean])
            ->groupBy('content_chunks.article_translation_id')
            ->orderByDesc('score')
            ->orderBy('content_chunks.article_translation_id')
            ->limit(self::LIMIT)
            ->pluck('content_chunks.article_translation_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function termMatches(string $locale, string $normalized): array
    {
        $terms = array_values(array_filter(explode(' ', $normalized), fn (string $term): bool => mb_strlen($term) >= 2));
        $query = $this->liveChunks($locale)->select('content_chunks.article_translation_id');

        foreach ($terms as $term) {
            $query->where('content_chunks.text_normalized', 'like', '%'.addcslashes($term, '%_\\').'%');
        }

        return $query->distinct()
            ->orderBy('content_chunks.article_translation_id')
            ->limit(self::LIMIT)
            ->pluck('content_chunks.article_translation_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return Builder<ContentChunk>
     */
    private function liveChunks(string $locale): Builder
    {
        return ContentChunk::query()
            ->join('article_translations', 'article_translations.id', '=', 'content_chunks.article_translation_id')
            ->where('content_chunks.locale', $locale)
            ->where('article_translations.publish_status', 'published')
            ->whereColumn('content_chunks.article_revision_id', 'article_translations.published_revision_id');
    }
}
