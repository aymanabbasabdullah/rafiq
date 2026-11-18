<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiMode;
use App\Enums\ArticleKind;
use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Models\ArticleTranslation;
use App\Models\ContentChunk;
use App\Models\GlossaryTermTranslation;
use App\Services\Ai\Data\GroundingChunk;
use App\Services\Content\ArabicNormalizer;
use App\Services\Content\KnowledgeUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Finds the approved content the model may use (ADR-008, Knowledge-Grounding):
 * published, medically approved revisions in the visitor's language, in topics and articles marked
 * `ai_enabled`, never backed by a source that restricts AI use. At most N chunks.
 */
final class GroundingRetriever
{
    private const STOPWORDS = ['what', 'when', 'where', 'which', 'with', 'from', 'that', 'this', 'about', 'does', 'have', 'into', 'your', 'explain', 'mean', 'means', 'please', 'should', 'there', 'their', 'the', 'and', 'are', 'for', 'you', 'how', 'can', 'why', 'who', 'its', 'not', 'was', 'has', 'will', 'would', 'could', 'they', 'them', 'than', 'then', 'also', 'some', 'any', 'all', 'get', 'tell',
        'ماذا', 'متي', 'كيف', 'لماذا', 'هل', 'عن', 'الي', 'علي', 'في', 'من', 'ما', 'هذا', 'هذه', 'التي', 'الذي', 'اشرح', 'معني', 'يعني', 'ممكن', 'لو', 'سمحت', 'يكون', 'تكون', 'كان', 'هو', 'هي', 'عند', 'كل', 'او', 'ام', 'لا', 'مع', 'بعد', 'قبل', 'اي', 'ايش', 'شو', 'وش'];

    public function __construct(private readonly ArabicNormalizer $normalizer, private readonly KnowledgeUrls $urls) {}

    /**
     * @return list<GroundingChunk>
     */
    public function retrieve(AiMode $mode, string $locale, string $query, ?int $articleId, ?int $termId, int $limit): array
    {
        return match ($mode) {
            AiMode::ExplainArticle => $articleId !== null ? $this->articleChunks($locale, $articleId, $limit) : [],
            AiMode::ExplainTerm => $termId !== null ? $this->termChunk($locale, $termId) : [],
            AiMode::PlatformGuide => $this->rankedChunks($locale, $query, $limit, platformGuide: true),
            AiMode::GeneralEducation => $this->rankedChunks($locale, $query, $limit, platformGuide: false),
        };
    }

    /**
     * Whether the article is live in this language and allowed for AI explanations (M09-FR-02 validation).
     */
    public function articleIsAvailable(string $locale, int $articleId): bool
    {
        return $this->eligibleTranslations($locale)->where('article_id', $articleId)->exists();
    }

    public function termIsAvailable(string $locale, int $termId): bool
    {
        return $this->terms($locale)->where('glossary_term_id', $termId)->exists();
    }

    /**
     * @return Builder<ArticleTranslation>
     */
    private function eligibleTranslations(string $locale): Builder
    {
        return ArticleTranslation::query()
            ->live($locale)
            ->whereHas('article', fn (Builder $article) => $article
                ->where('ai_enabled', true)
                ->whereHas('category', fn (Builder $category) => $category->where('is_visible', true)->where('ai_enabled', true))
                ->whereDoesntHave('sources', fn (Builder $source) => $source->where('ai_use_restricted', true)));
    }

    /**
     * @return Builder<GlossaryTermTranslation>
     */
    private function terms(string $locale): Builder
    {
        return GlossaryTermTranslation::query()
            ->where('locale', $locale)
            ->where('review_status', ReviewStatus::Approved->value)
            ->where('publish_status', PublishStatus::Published->value);
    }

    /**
     * @return list<GroundingChunk>
     */
    private function articleChunks(string $locale, int $articleId, int $limit): array
    {
        $translation = $this->eligibleTranslations($locale)->where('article_id', $articleId)->with(['publishedRevision', 'article.category.translations'])->first();

        if ($translation === null) {
            return [];
        }

        $chunks = ContentChunk::query()
            ->where('article_revision_id', $translation->published_revision_id)
            ->orderBy('chunk_index')
            ->limit($limit)
            ->get();

        return $this->toGrounding($chunks, collect([$translation->id => $translation]));
    }

    /**
     * @return list<GroundingChunk>
     */
    private function termChunk(string $locale, int $termId): array
    {
        $term = $this->terms($locale)->where('glossary_term_id', $termId)->first();

        if ($term === null) {
            return [];
        }

        return [new GroundingChunk(
            id: "g{$term->id}",
            heading: $term->term,
            text: $term->definition_markdown,
            title: $term->term,
            url: route('glossary.index', ['locale' => $locale]),
            articleId: null,
        )];
    }

    /**
     * Scores live chunks by how many distinct query words they contain. Content volume is small, so this
     * runs in PHP and behaves the same on every database driver.
     *
     * @return list<GroundingChunk>
     */
    private function rankedChunks(string $locale, string $query, int $limit, bool $platformGuide): array
    {
        $translations = $this->eligibleTranslations($locale)
            ->whereHas('article', fn (Builder $article) => $platformGuide
                ? $article->where('kind', ArticleKind::PlatformGuide->value)
                : $article->where('kind', '!=', ArticleKind::PlatformGuide->value))
            ->with(['publishedRevision', 'article.category.translations'])
            ->get()
            ->keyBy('id');

        if ($translations->isEmpty()) {
            return [];
        }

        $chunks = ContentChunk::query()
            ->whereIn('article_revision_id', $translations->pluck('published_revision_id')->filter()->all())
            ->orderBy('article_translation_id')
            ->orderBy('chunk_index')
            ->limit(500)
            ->get();

        $terms = array_values(array_unique(array_map(fn (string $term): string => $this->stem($term), $this->queryTerms($query))));

        $indexed = $chunks->map(fn (ContentChunk $chunk): array => [
            'chunk' => $chunk,
            'heading' => $this->stems($this->normalizer->normalize($translations->get($chunk->article_translation_id)?->publishedRevision?->title.' '.$chunk->heading)),
            'text' => $this->stems($chunk->text_normalized),
        ]);

        // Rarer words count more (inverse document frequency over the candidate chunks).
        $weights = [];

        foreach ($terms as $term) {
            $frequency = $indexed->filter(fn (array $item): bool => isset($item['heading'][$term]) || isset($item['text'][$term]))->count();
            $weights[$term] = $frequency === 0 ? 0.0 : 1 + log($indexed->count() / $frequency);
        }

        $scored = $indexed
            ->map(fn (array $item): array => ['chunk' => $item['chunk'], 'score' => $this->score($item['heading'], $item['text'], $weights)])
            ->filter(fn (array $item): bool => $item['score'] > 0 || ($platformGuide && $terms === []))
            ->sortByDesc('score')
            // At most two chunks per article, so one long article cannot crowd out the others.
            ->groupBy(fn (array $item): int => $item['chunk']->article_translation_id)
            ->flatMap(fn (Collection $items): Collection => $items->take(2))
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('chunk');

        if ($scored->isEmpty() && $platformGuide) {
            // How-to questions phrased in unexpected words still get the platform guide.
            $scored = $chunks->take($limit);
        }

        return $this->toGrounding($scored->values(), $translations);
    }

    /**
     * @return list<string>
     */
    private function queryTerms(string $query): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($this->normalizer->normalize($query))) ?: [];

        return array_values(array_unique(array_filter($words, fn (string $word): bool => mb_strlen($word) >= 3 && ! in_array($word, self::STOPWORDS, true))));
    }

    /**
     * Sum of word weights (whole words, compared by light stem), doubled in the article title or chunk heading.
     *
     * @param  array<string, true>  $heading
     * @param  array<string, true>  $text
     * @param  array<string, float>  $weights
     */
    private function score(array $heading, array $text, array $weights): float
    {
        $score = 0.0;

        foreach ($weights as $term => $weight) {
            $score += isset($heading[$term]) ? 2 * $weight : (isset($text[$term]) ? $weight : 0.0);
        }

        return $score;
    }

    /**
     * @return array<string, true>
     */
    private function stems(string $normalized): array
    {
        $stems = [];

        foreach (preg_split('/[^\p{L}\p{N}]+/u', $normalized) ?: [] as $word) {
            if ($word !== '') {
                $stems[$this->stem($word)] = true;
            }
        }

        return $stems;
    }

    /**
     * Very light stemming: common Arabic prefixes/suffixes and the English plural "s".
     */
    private function stem(string $word): string
    {
        $stem = (string) preg_replace(['/^(وال|بال|كال|فال|لل|ال)/u', '/(ات|ين|ون|ها|ه|ا|ي)$/u', '/(?<=[a-z]{3})s$/u'], '', $word);

        return mb_strlen($stem) >= 3 ? $stem : $word;
    }

    /**
     * @param  Collection<int, ContentChunk>  $chunks
     * @param  Collection<int, ArticleTranslation>  $translations
     * @return list<GroundingChunk>
     */
    private function toGrounding(Collection $chunks, Collection $translations): array
    {
        $grounding = [];

        foreach ($chunks as $chunk) {
            $translation = $translations->get($chunk->article_translation_id);
            $url = $translation !== null ? $this->urls->article($translation) : null;

            if ($translation === null || $url === null || $translation->publishedRevision === null) {
                continue;
            }

            $grounding[] = new GroundingChunk(
                id: "c{$chunk->id}",
                heading: (string) $chunk->heading,
                text: $chunk->text,
                title: $translation->publishedRevision->title,
                url: $url,
                articleId: $translation->article_id,
            );
        }

        return $grounding;
    }
}
